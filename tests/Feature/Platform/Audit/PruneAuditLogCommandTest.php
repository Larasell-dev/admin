<?php

declare(strict_types=1);

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Audit\Actors\SystemActor;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Chain\AuditChain;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));
});

/**
 * Records an action that happened the given time ago, and returns its place in the chain.
 */
function storedRecord(int $daysAgo, int $andSeconds = 0): int
{
    $at = now()->subDays($daysAgo)->subSeconds($andSeconds);

    DB::transaction(fn () => app(Auditor::class)->record(new class($at) extends AuditRecord
    {
        public function __construct(private readonly CarbonInterface $at) {}

        public function event(): string
        {
            return 'book.archived';
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

        public function occurredAt(): CarbonInterface
        {
            return $this->at;
        }
    }, new SystemActor('tests')));

    return (int) DB::table('larasell_audit_log')->max('seq');
}

/**
 * @return list<int>
 */
function remainingRecords(): array
{
    return DB::table('larasell_audit_log')->where('event', 'book.archived')->orderBy('seq')->pluck('seq')->map(fn ($seq) => (int) $seq)->all();
}

/**
 * The newest prune record's properties.
 *
 * @return array<string, mixed>
 */
function pruneProperties(): array
{
    return json_decode(DB::table('larasell_audit_log')->where('event', 'audit_log.pruned')->orderByDesc('seq')->value('properties'), true);
}

it('keeps every record by default', function () {
    // Arrange
    $old = storedRecord(5000);

    // Act
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    // Assert
    expect(remainingRecords())->toBe([$old])
        ->and(pruneProperties())->toBe(['legal_hold' => false, 'retention_days' => null, 'deleted' => 0, 'starts_after' => null]);
});

it('deletes the oldest records older than the retention period, and the chain still verifies', function () {
    // Arrange
    config(['larasell.admin.audit.retention.days' => 30]);

    storedRecord(400);
    storedRecord(31);
    $kept = storedRecord(29);
    $deletedHash = DB::table('larasell_audit_log')->where('seq', 2)->value('hash');

    // Act
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    // Assert
    expect(remainingRecords())->toBe([$kept])
        ->and(pruneProperties())->toBe(['legal_hold' => false, 'retention_days' => 30, 'deleted' => 2, 'starts_after' => ['seq' => 2, 'sha256' => $deletedHash]])
        ->and(app(AuditChain::class)->verify()->passed)->toBeTrue();
});

it('only deletes from the start, keeping an old record behind a newer one', function () {
    // Arrange
    config(['larasell.admin.audit.retention.days' => 30]);

    storedRecord(400);
    $newer = storedRecord(1);
    $backdated = storedRecord(400);

    // Act
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    // Assert
    expect(remainingRecords())->toBe([$newer, $backdated])
        ->and(app(AuditChain::class)->verify()->passed)->toBeTrue();
});

it('keeps a record until it is older than the retention period, to the second', function () {
    // Arrange
    config(['larasell.admin.audit.retention.days' => 30]);

    storedRecord(30, andSeconds: 1);
    $exactly = storedRecord(30);

    // Act
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    // Assert
    expect(remainingRecords())->toBe([$exactly]);
});

it('keeps where the chain starts when a later run deletes nothing, or everything', function () {
    // Arrange
    config(['larasell.admin.audit.retention.days' => 30]);

    storedRecord(400);
    storedRecord(10);
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();
    $anchor = pruneProperties()['starts_after'];

    // Act & Assert
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    expect(pruneProperties()['starts_after'])->toBe($anchor)
        ->and(app(AuditChain::class)->verify()->passed)->toBeTrue();

    $this->travel(400)->days();
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    expect(DB::table('larasell_audit_log')->count())->toBe(1)
        ->and(pruneProperties()['starts_after']['seq'])->toBe(4)
        ->and(app(AuditChain::class)->verify()->passed)->toBeTrue();
});

it('reads a period set as a string, as env() returns it', function () {
    // Arrange
    config(['larasell.admin.audit.retention.days' => '30']);

    storedRecord(31);

    // Act
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    // Assert
    expect(remainingRecords())->toBe([])
        ->and(pruneProperties()['retention_days'])->toBe(30);
});

it('deletes nothing under a legal hold, and records that it was held', function () {
    // Arrange
    config(['larasell.admin.audit.retention.days' => 30, 'larasell.admin.audit.legal_hold' => true]);

    $old = storedRecord(400);

    // Act
    $this->artisan('larasell:prune-audit-log')
        ->expectsOutputToContain('legal hold')
        ->assertSuccessful();

    // Assert
    expect(remainingRecords())->toBe([$old])
        ->and(pruneProperties())->toMatchArray(['legal_hold' => true, 'deleted' => 0]);
});

it('records each run as the command', function () {
    // Act
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    // Assert
    expect(DB::table('larasell_audit_log')->where('event', 'audit_log.pruned')->sole())->toMatchArray([
        'category' => 'audit_access',
        'actor_type' => 'system',
        'actor_id' => 'larasell:prune-audit-log',
        'source' => 'cli',
        'subject_type' => null,
    ]);
});

it('refuses an invalid retention period without deleting anything', function (mixed $days) {
    // Arrange
    config(['larasell.admin.audit.retention.days' => $days]);

    $old = storedRecord(400);

    // Act
    $this->artisan('larasell:prune-audit-log')->assertFailed();

    // Assert
    expect(remainingRecords())->toBe([$old])
        ->and(DB::table('larasell_audit_log')->where('event', 'audit_log.pruned')->exists())->toBeFalse();
})->with([
    'zero days' => [0],
    'negative days' => [-30],
    'not a number' => ['a year'],
    'a fraction' => [30.5],
]);
