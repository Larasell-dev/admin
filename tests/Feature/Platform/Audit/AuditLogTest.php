<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Larasell\Admin\ApiToken\Models\ApiToken;
use Larasell\Admin\Platform\Audit\Actor;
use Larasell\Admin\Platform\Audit\AuditCategory;
use Larasell\Admin\Platform\Audit\AuditFailed;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Admin\Platform\Audit\SystemActor;
use Larasell\Admin\Platform\Audit\UnknownActor;
use Larasell\Admin\Platform\Auth\Models\Admin;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));

    // Jobs run during the request, whatever queue the environment configures.
    config(['queue.default' => 'sync']);

    $this->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
});

/**
 * A record as a module or extension defines one.
 *
 * @param  array<string, mixed>  $properties
 */
function bookArchived(array $properties = ['title' => 'Dune'], ?CarbonInterface $occurredAt = null): AuditRecord
{
    return new class($properties, $occurredAt) extends AuditRecord
    {
        /**
         * @param  array<string, mixed>  $properties
         */
        public function __construct(private readonly array $properties, private readonly ?CarbonInterface $at) {}

        public function event(): string
        {
            return 'book.archived';
        }

        public function version(): int
        {
            return 2;
        }

        public function category(): AuditCategory
        {
            return AuditCategory::BusinessCritical;
        }

        public function subject(): ?Subject
        {
            return new Subject('book', 42);
        }

        public function properties(): array
        {
            return $this->properties;
        }

        public function occurredAt(): CarbonInterface
        {
            return $this->at ?? parent::occurredAt();
        }
    };
}

/**
 * Records in a transaction, as every audited change does.
 */
function audit(AuditRecord $record, Actor|UnknownActor|null $actor = null): void
{
    DB::transaction(fn () => app(Auditor::class)->record($record, $actor));
}

it('stores a record with its event, actor, subject and properties', function () {
    // Act
    audit(bookArchived(), new SystemActor('inventory-reconciliation'));

    // Assert
    $record = DB::table('larasell_audit_log')->sole();

    expect(Str::isUlid($record->id))->toBeTrue()
        ->and($record)->toMatchArray([
            'event' => 'book.archived',
            'event_version' => 2,
            'category' => 'business_critical',
            'actor_type' => 'system',
            'actor_id' => 'inventory-reconciliation',
            'unknown_actor_reason' => null,
            'subject_type' => 'book',
            'subject_id' => '42',
            'source' => 'cli',
            'occurred_at' => '2026-10-30 12:00:00.000000',
            'created_at' => '2026-10-30 12:00:00.000000',
        ])
        ->and(json_decode($record->properties, true))->toBe(['title' => 'Dune'])
        ->and($record->correlation_id)->toBeString()->not->toBeEmpty();
});

it('has no updated_at, since records never change', function () {
    // Act & Assert
    expect(Schema::getColumnListing('larasell_audit_log'))->toContain('created_at')->not->toContain('updated_at');
});

it('stores times in UTC, and when it happened apart from when it was recorded', function () {
    // Arrange
    config(['app.timezone' => 'Europe/Berlin']);
    date_default_timezone_set('Europe/Berlin');

    try {
        // 14:00 in Berlin is 13:00 UTC.
        $this->travelTo(Carbon::parse('2026-10-30 14:00:00'));

        // Act
        audit(bookArchived(occurredAt: now()->subMinutes(5)), new SystemActor('inventory-reconciliation'));

        // Assert
        expect(DB::table('larasell_audit_log')->sole())->toMatchArray([
            'occurred_at' => '2026-10-30 12:55:00.000000',
            'created_at' => '2026-10-30 13:00:00.000000',
        ]);
    } finally {
        date_default_timezone_set('UTC');
    }
});

it('refuses to record outside a transaction, where the change could commit without its record', function () {
    // Act & Assert
    expect(fn () => app(Auditor::class)->record(bookArchived(), new SystemActor('inventory-reconciliation')))
        ->toThrow(AuditFailed::class);

    expect(DB::table('larasell_audit_log')->count())->toBe(0);
});

it('records outside a transaction when the record changes nothing it must commit with', function () {
    // Arrange
    $signInFailed = new class extends AuditRecord
    {
        public function event(): string
        {
            return 'admin.sign_in_failed';
        }

        public function category(): AuditCategory
        {
            return AuditCategory::Security;
        }

        public function subject(): ?Subject
        {
            return null;
        }

        public function properties(): array
        {
            return [];
        }

        public function requiresTransaction(): bool
        {
            return false;
        }
    };

    // Act
    app(Auditor::class)->record($signInFailed, new UnknownActor('No one was signed in.'));

    // Assert
    expect(DB::table('larasell_audit_log')->sole()->event)->toBe('admin.sign_in_failed');
});

it('requires an explicit actor on the command line', function () {
    // Act & Assert
    expect(fn () => audit(bookArchived()))->toThrow(AuditFailed::class);

    expect(DB::table('larasell_audit_log')->count())->toBe(0);
});

it('stores an unknown actor with the reason it is unknown', function () {
    // Act
    audit(bookArchived(), new UnknownActor('Imported from the previous shop system.'));

    // Assert
    expect(DB::table('larasell_audit_log')->sole())->toMatchArray([
        'actor_type' => 'unknown',
        'actor_id' => null,
        'unknown_actor_reason' => 'Imported from the previous shop system.',
    ]);
});

it('requires a name for system actors and a reason for unknown ones', function (Closure $actor) {
    // Act & Assert
    expect($actor)->toThrow(AuditFailed::class);
})->with([
    'a system actor without a name' => [fn () => new SystemActor('')],
    'an unknown actor without a reason' => [fn () => new UnknownActor('  ')],
]);

it('records the API token as the actor of API requests', function () {
    // Arrange
    $apiToken = ApiToken::query()->create(['name' => 'ERP sync', 'token_hash' => hash('sha256', 'larasell_erp'), 'expires_at' => now()->addMonth()]);
    Route::middleware(['api', 'larasell.api'])->post('api/books/archive', fn () => audit(bookArchived()));

    // Act
    $this->postJson('api/books/archive', headers: ['Authorization' => 'Bearer larasell_erp'])->assertOk();

    // Assert
    expect(DB::table('larasell_audit_log')->sole())->toMatchArray([
        'actor_type' => 'api_token',
        'actor_id' => (string) $apiToken->id,
        'source' => 'api',
    ]);
});

it('shares the correlation ID of a request with the jobs it dispatches, which record as jobs', function () {
    // Arrange
    Route::middleware('web')->post('books/archive', function () {
        audit(bookArchived());
        dispatch(static function (): void {
            audit(bookArchived(), new SystemActor('book-indexer'));
        });
        audit(bookArchived());
    });

    // Act
    $this->actingAs($this->ada, 'larasell-admin')->post('books/archive')->assertOk();

    // Assert
    $records = DB::table('larasell_audit_log')->orderBy('id')->get();

    expect($records->pluck('source')->all())->toBe(['web', 'job', 'web'])
        ->and($records->pluck('actor_type')->all())->toBe(['admin', 'system', 'admin'])
        ->and($records[0]->actor_id)->toBe((string) $this->ada->id)
        ->and($records->pluck('correlation_id')->unique())->toHaveCount(1);
});

it('requires an explicit actor in jobs, even ones a signed-in admin\'s request runs', function () {
    // Arrange
    Route::middleware('web')->post('books/archive', function (): void {
        dispatch(static function (): void {
            audit(bookArchived());
        });
    });
    Route::middleware('web')->post('books/restore', fn () => audit(bookArchived()));

    // Act
    $this->actingAs($this->ada, 'larasell-admin')->post('books/archive')->assertServerError();

    // Assert
    expect(DB::table('larasell_audit_log')->count())->toBe(0);

    // The failed job is over, so the next request's record is the admin's again.
    $this->post('books/restore')->assertOk();

    expect(DB::table('larasell_audit_log')->sole())->toMatchArray(['source' => 'web', 'actor_type' => 'admin']);
});

it('rejects properties that look like credentials', function (array $properties) {
    // Act & Assert
    expect(fn () => audit(bookArchived($properties), new SystemActor('inventory-reconciliation')))
        ->toThrow(AuditFailed::class);

    expect(DB::table('larasell_audit_log')->count())->toBe(0);
})->with([
    'a password' => [['password' => 'secret123']],
    'a token hash' => [['token_hash' => 'abc']],
    'a camel-cased token' => [['apiToken' => 'larasell_abc']],
    'a client secret' => [['client_secret' => 'abc']],
    'nested credentials' => [['integration' => ['name' => 'ERP', 'credentials' => ['user' => 'erp']]]],
]);

it('rejects properties that aren\'t plain values, so each record decides how to store them', function (array $properties) {
    // Act & Assert
    expect(fn () => audit(bookArchived($properties), new SystemActor('inventory-reconciliation')))
        ->toThrow(AuditFailed::class);
})->with([
    'a model' => [fn () => ['admin' => Admin::query()->first()]],
    'a date' => [fn () => ['archived_at' => now()]],
    'a nested object' => [fn () => ['book' => ['archived_at' => now()]]],
]);
