<?php

namespace Larasell\Admin\Platform\Audit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Larasell\Admin\Platform\Audit\Actors\Actor;
use Larasell\Admin\Platform\Audit\Actors\UnknownActor;
use Larasell\Admin\Platform\Audit\Chain\AuditChain;
use Larasell\Admin\Platform\Audit\Enums\AuditSource;
use Larasell\Admin\Platform\Audit\Exceptions\AuditFailed;

/**
 * Writes audit records to the shop's database, the audit log's source of truth. Records can only
 * be added, never changed through the app, and each is chained to the one before it, so changing
 * or deleting one outside the app shows when the log is verified.
 *
 * Record in the transaction of the change, so both commit or neither does. Records of actions
 * that change nothing, such as a failed sign-in, opt out with requiresTransaction().
 *
 *     DB::transaction(function () use ($auditor) {
 *         // the change
 *         $auditor->record(new ApiTokenCreated($apiToken));
 *     });
 *
 * @public Inject it, or use the Audit facade, and call record(); its other methods are internal.
 * Tests fake it with Audit::fake().
 */
class Auditor
{
    /**
     * Property names made of any of these words are rejected, e.g. password, token_hash or apiToken.
     */
    private const array CREDENTIAL_WORDS = ['password', 'passwords', 'secret', 'secrets', 'token', 'tokens', 'hash', 'credential', 'credentials'];

    /**
     * The Laravel context key, so logs of the same request or job carry the ID too, and jobs
     * dispatched from a request inherit it.
     */
    private const string CORRELATION_ID = 'correlation_id';

    protected int $runningJobs = 0;

    /** The guard that authenticates API requests, once a module registers one. */
    protected ?string $apiGuard = null;

    /**
     * What the audit log calls actors other than admins, by actor type.
     *
     * @var array<string, array{label: string, model: class-string<Model>}>
     */
    protected array $actorTypes = [];

    public function __construct(protected readonly AuditChain $chain) {}

    /**
     * Stores the record, failing rather than losing it, so the change it describes rolls back too.
     *
     * The actor is the signed-in admin, or the API token of an API request. Jobs and commands pass
     * theirs, e.g. a SystemActor, and an action whose actor can't be known passes an UnknownActor.
     *
     * @public
     *
     * @throws AuditFailed
     */
    public function record(AuditRecord $record, Actor|UnknownActor|null $actor = null): void
    {
        $event = $record->event();

        if ($record->requiresTransaction() && DB::transactionLevel() === 0) {
            throw AuditFailed::outsideTransaction($event);
        }

        $source = $this->source();
        $actor ??= $this->resolveActor($event, $source);
        $properties = $record->properties();
        $this->validate($event, $properties);
        $subject = $record->subject();

        $this->store($record, $actor, $source, [
            'id' => (string) Str::ulid(),
            'event' => $event,
            'event_version' => $record->version(),
            'category' => $record->category()->value,
            'actor_type' => $actor instanceof Actor ? $actor->auditActorType() : 'unknown',
            'actor_id' => $actor instanceof Actor ? $actor->auditActorId() : null,
            'unknown_actor_reason' => $actor instanceof UnknownActor ? $actor->reason : null,
            'subject_type' => $subject?->type,
            'subject_id' => $subject?->id,
            // An object even when empty, so every record's properties have the same JSON type.
            'properties' => $properties === [] ? '{}' : json_encode($properties, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'source' => $source->value,
            'correlation_id' => $this->correlationId(),
            // copy(), since the record may return a model's own date.
            'occurred_at' => $record->occurredAt()->copy()->utc()->format('Y-m-d H:i:s.u'),
            'created_at' => Date::now()->utc()->format('Y-m-d H:i:s.u'),
        ]);
    }

    /**
     * Records an audit record for an event the core dispatches, which may come from a request, a
     * job or a command. Outside requests the actor can't be known, so it is recorded as unknown with
     * the reason, rather than failing the change the event describes.
     *
     * @public For listeners of core events.
     *
     * @throws AuditFailed
     */
    public function recordForEvent(AuditRecord $record, string $unknownReason): void
    {
        $source = $this->source();

        try {
            $actor = $this->resolveActor($record->event(), $source);
        } catch (AuditFailed) {
            $actor = new UnknownActor($unknownReason);
        }

        $this->record($record, $actor);
    }

    /**
     * @internal Called by the queue's events, so records made in a job say so.
     */
    public function jobStarted(): void
    {
        $this->runningJobs++;
    }

    /**
     * @internal Called by the queue's events.
     */
    public function jobEnded(): void
    {
        $this->runningJobs = max(0, $this->runningJobs - 1);
    }

    /**
     * @internal Called by the module whose guard authenticates API requests, so records made in
     * them get the api source.
     */
    public function apiGuard(string $guard): void
    {
        $this->apiGuard = $guard;
    }

    /**
     * @internal Called by the modules whose models act, so the audit log names their actors, e.g.
     * "API token: Deploy", from the model's name attribute. The label is marked with
     * Translate::later(), since it's registered while the app boots; the audit log translates it.
     *
     * @param  class-string<Model>  $model
     */
    public function actorType(string $type, string $label, string $model): void
    {
        $this->actorTypes[$type] = ['label' => $label, 'model' => $model];
    }

    /**
     * @internal
     *
     * @return array{label: string, model: class-string<Model>}|null
     */
    public function findActorType(string $type): ?array
    {
        return $this->actorTypes[$type] ?? null;
    }

    /**
     * Appends the checked record to the chain. AuditFake keeps it in memory instead.
     *
     * @param  array<string, mixed>  $row
     */
    protected function store(AuditRecord $record, Actor|UnknownActor $actor, AuditSource $source, array $row): void
    {
        $this->chain->append($row);
    }

    private function source(): AuditSource
    {
        if ($this->runningJobs > 0) {
            return AuditSource::Job;
        }

        // Tests run in the console too, but handle their requests with a route.
        if (app()->runningInConsole() && request()->route() === null) {
            return AuditSource::Cli;
        }

        return $this->apiGuard !== null && Auth::getDefaultDriver() === $this->apiGuard ? AuditSource::Api : AuditSource::Web;
    }

    /**
     * Jobs and commands have no one signed in, and an admin whose request dispatched a job isn't
     * necessarily who the job acts for, so they must pass their actor.
     */
    private function resolveActor(string $event, AuditSource $source): Actor
    {
        if ($source === AuditSource::Web || $source === AuditSource::Api) {
            // The API token on API requests; admin pages don't make the admin guard the default.
            foreach ([Auth::guard(), Auth::guard('larasell-admin')] as $guard) {
                $user = $guard->user();

                if ($user instanceof Actor) {
                    return $user;
                }
            }
        }

        throw AuditFailed::missingActor($event, $source->value);
    }

    /**
     * @param  array<array-key, mixed>  $properties
     */
    private function validate(string $event, array $properties, string $path = ''): void
    {
        foreach ($properties as $key => $value) {
            $keyPath = ltrim($path.'.'.$key, '.');

            if (is_string($key) && array_intersect(explode('_', Str::snake($key)), self::CREDENTIAL_WORDS) !== []) {
                throw AuditFailed::credentialProperty($event, $keyPath);
            }

            if (is_array($value)) {
                $this->validate($event, $value, $keyPath);
            } elseif (is_float($value) || ! is_scalar($value) && $value !== null) {
                throw AuditFailed::invalidProperty($event, $keyPath, get_debug_type($value));
            }
        }
    }

    private function correlationId(): string
    {
        $id = Context::get(self::CORRELATION_ID);

        if (! is_string($id) || $id === '') {
            $id = (string) Str::uuid();
            Context::add(self::CORRELATION_ID, $id);
        }

        return $id;
    }
}
