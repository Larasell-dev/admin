<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));
});

/**
 * Stores a record of the category that happened the given time ago, and returns its ID.
 */
function storedRecord(string $category, int $daysAgo, int $andSeconds = 0): string
{
    $id = (string) Str::ulid();

    DB::table('larasell_audit_log')->insert([
        'id' => $id,
        'event' => 'book.archived',
        'event_version' => 1,
        'category' => $category,
        'actor_type' => 'system',
        'actor_id' => 'tests',
        'properties' => '{}',
        'source' => 'cli',
        'occurred_at' => now()->utc()->subDays($daysAgo)->subSeconds($andSeconds)->format('Y-m-d H:i:s.u'),
        'created_at' => now()->utc()->format('Y-m-d H:i:s.u'),
    ]);

    return $id;
}

/**
 * @return list<string>
 */
function remainingRecords(): array
{
    return DB::table('larasell_audit_log')->where('event', 'book.archived')->orderBy('id')->pluck('id')->all();
}

/**
 * @return array<string, mixed>
 */
function pruneRecord(): array
{
    $records = DB::table('larasell_audit_log')->where('event', 'audit_log.pruned')->get();

    expect($records)->toHaveCount(1);

    return [...(array) $records->first(), 'properties' => json_decode($records->first()->properties, true)];
}

it('keeps every record by default', function () {
    $old = storedRecord('security', 5000);

    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    expect(remainingRecords())->toBe([$old])
        ->and(pruneRecord()['properties'])->toBe([
            'legal_hold' => false,
            'retention_days' => ['security' => null, 'business_critical' => null, 'audit_access' => null],
            'deleted' => ['security' => 0, 'business_critical' => 0, 'audit_access' => 0],
        ]);
});

it('deletes records older than the default retention period', function () {
    config(['larasell.admin.audit.retention.default_days' => 30]);

    storedRecord('security', 31);
    $kept = storedRecord('security', 29);
    storedRecord('audit_access', 400);

    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    expect(remainingRecords())->toBe([$kept])
        ->and(pruneRecord()['properties']['deleted'])->toBe(['security' => 1, 'business_critical' => 0, 'audit_access' => 1]);
});

it('keeps a record until it is older than the retention period, to the second', function () {
    config(['larasell.admin.audit.retention.default_days' => 30]);

    $exactly = storedRecord('security', 30);
    storedRecord('security', 30, andSeconds: 1);

    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    expect(remainingRecords())->toBe([$exactly]);
});

it('keeps each category for its own period, falling back to the default', function () {
    config(['larasell.admin.audit.retention' => [
        'default_days' => 30,
        'categories' => ['security' => 730, 'audit_access' => null],
    ]]);

    $security = storedRecord('security', 400);
    storedRecord('security', 731);
    storedRecord('business_critical', 31);
    $auditAccess = storedRecord('audit_access', 5000);

    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    expect(remainingRecords())->toEqualCanonicalizing([$security, $auditAccess])
        ->and(pruneRecord()['properties'])->toBe([
            'legal_hold' => false,
            'retention_days' => ['security' => 730, 'business_critical' => 30, 'audit_access' => null],
            'deleted' => ['security' => 1, 'business_critical' => 1, 'audit_access' => 0],
        ]);
});

it('reads periods set as strings, as env() returns them', function () {
    config(['larasell.admin.audit.retention.default_days' => '30']);

    storedRecord('security', 31);

    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    expect(remainingRecords())->toBe([])
        ->and(pruneRecord()['properties']['retention_days']['security'])->toBe(30);
});

it('deletes nothing under a legal hold, and records that it was held', function () {
    config(['larasell.admin.audit.retention.default_days' => 30, 'larasell.admin.audit.legal_hold' => true]);

    $old = storedRecord('security', 400);

    $this->artisan('larasell:prune-audit-log')
        ->expectsOutputToContain('legal hold')
        ->assertSuccessful();

    expect(remainingRecords())->toBe([$old])
        ->and(pruneRecord()['properties'])->toMatchArray([
            'legal_hold' => true,
            'deleted' => ['security' => 0, 'business_critical' => 0, 'audit_access' => 0],
        ]);
});

it('records each run as the command, so the retention settings it applied are in the log', function () {
    config(['larasell.admin.audit.retention.default_days' => 30]);

    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    expect(pruneRecord())->toMatchArray([
        'category' => 'audit_access',
        'actor_type' => 'system',
        'actor_id' => 'larasell:prune-audit-log',
        'source' => 'cli',
        'subject_type' => null,
    ]);
});

it('refuses invalid retention settings without deleting anything', function (array $retention) {
    config(['larasell.admin.audit.retention' => $retention]);

    $old = storedRecord('security', 400);

    $this->artisan('larasell:prune-audit-log')->assertFailed();

    expect(remainingRecords())->toBe([$old])
        ->and(DB::table('larasell_audit_log')->where('event', 'audit_log.pruned')->exists())->toBeFalse();
})->with([
    'zero days' => [['default_days' => 0]],
    'negative days' => [['default_days' => -30]],
    'not a number' => [['default_days' => 'a year']],
    'a fraction' => [['default_days' => 30.5]],
    'an unknown category' => [['default_days' => 30, 'categories' => ['securty' => 730]]],
    'an invalid category period' => [['categories' => ['security' => '0']]],
]);
