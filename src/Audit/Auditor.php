<?php

namespace Larasell\Admin\Audit;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Larasell\Admin\Exceptions\AuditFailed;
use Larasell\Admin\Http\Middleware\ApiTokens\AuthenticateApiToken;

/**
 * Writes audit records to the shop's database, the audit log's source of truth. Records can only
 * be added, never changed through the app.
 *
 * Record in the transaction of the change, so both commit or neither does:
 *
 *     DB::transaction(function () use ($auditor) {
 *         // the change
 *         $auditor->record(new ApiTokenCreated($apiToken));
 *     });
 *
 * @public Inject it and call record(); its other methods are internal.
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

    private int $runningJobs = 0;

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

        if (DB::transactionLevel() === 0) {
            throw AuditFailed::outsideTransaction($event);
        }

        $source = $this->source();
        $actor ??= $this->resolveActor($event, $source);
        $properties = $record->properties();
        $this->validate($event, $properties);
        $subject = $record->subject();

        DB::table('larasell_audit_log')->insert([
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

    private function source(): AuditSource
    {
        if ($this->runningJobs > 0) {
            return AuditSource::Job;
        }

        // Tests run in the console too, but handle their requests with a route.
        if (app()->runningInConsole() && request()->route() === null) {
            return AuditSource::Cli;
        }

        return Auth::getDefaultDriver() === AuthenticateApiToken::GUARD ? AuditSource::Api : AuditSource::Web;
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
            } elseif (! is_scalar($value) && $value !== null) {
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
