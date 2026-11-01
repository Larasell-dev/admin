<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;
use Larasell\Admin\Audit\SystemActor;
use Larasell\Admin\Audit\UnknownActor;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Models\ApiToken;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));

    $this->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
});

/**
 * Records an event the way modules do, at the given time.
 *
 * @param  array<string, mixed>  $properties
 */
function recordAt(string $time, string $event, mixed $actor, ?Subject $subject = null, array $properties = []): void
{
    test()->travelTo(Carbon::parse($time));

    DB::transaction(fn () => app(Auditor::class)->record(new class($event, $subject, $properties) extends AuditRecord
    {
        /**
         * @param  array<string, mixed>  $props
         */
        public function __construct(private readonly string $name, private readonly ?Subject $about, private readonly array $props) {}

        public function event(): string
        {
            return $this->name;
        }

        public function category(): AuditCategory
        {
            return AuditCategory::Security;
        }

        public function subject(): ?Subject
        {
            return $this->about;
        }

        public function properties(): array
        {
            return $this->props;
        }
    }, $actor));

    test()->travelTo(Carbon::parse('2026-10-30 12:00:00'));
}

/**
 * @param  array<string, string>  $filters
 */
function auditLogPage(array $filters = []): AssertableInertia
{
    $page = null;

    test()->actingAs(test()->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.audit-log.index', $filters))
        ->assertOk()
        ->assertInertia(function (AssertableInertia $inertia) use (&$page) {
            $page = $inertia->component('AuditLog/Index');
        });

    return $page;
}

/**
 * @param  array<string, string>  $filters
 * @return list<string>
 */
function listedEvents(array $filters = []): array
{
    $rows = auditLogPage($filters)->toArray()['props']['auditLog']['rows'];

    // Leaves out the page's own access records.
    return array_values(array_filter(array_column($rows, 'event'), fn (string $event): bool => ! str_starts_with($event, 'audit_log.')));
}

it('lists audit records newest first, with who did what to what', function () {
    $apiToken = ApiToken::query()->create(['name' => 'ERP sync', 'token_hash' => hash('sha256', 'larasell_erp'), 'expires_at' => now()->addMonth()]);
    recordAt('2026-10-29 09:00:00', 'api_token.created', $this->ada, new Subject('api_token', $apiToken->id), ['name' => 'ERP sync']);
    recordAt('2026-10-29 10:00:00', 'product_review.deleted', $apiToken, new Subject('product_review', 7), ['rating' => 4, 'status' => ['from' => 'pending', 'to' => 'approved']]);
    recordAt('2026-10-29 11:00:00', 'inventory.reconciled', new SystemActor('inventory-reconciliation'));
    recordAt('2026-10-29 12:00:00', 'order.imported', new UnknownActor('Imported from the previous shop system.'));

    $page = auditLogPage()->toArray()['props'];
    $rows = array_slice($page['auditLog']['rows'], 1);

    expect($page['auditLog']['columns'])->toBe([
        ['name' => 'occurred_at', 'label' => 'Time (UTC)', 'type' => 'text'],
        ['name' => 'event', 'label' => 'Event', 'type' => 'text'],
        ['name' => 'actor', 'label' => 'Actor', 'type' => 'text'],
        ['name' => 'subject', 'label' => 'Subject', 'type' => 'text'],
        ['name' => 'source', 'label' => 'Source', 'type' => 'text'],
        ['name' => 'details', 'label' => 'Details', 'type' => 'text'],
    ])
        ->and(array_column($rows, 'source'))->toBe(['CLI', 'CLI', 'CLI', 'CLI'])
        ->and(array_map(fn (array $row): array => array_intersect_key($row, array_flip(['occurred_at', 'event', 'actor', 'subject', 'details'])), $rows))->toBe([
            ['occurred_at' => 'Oct 29, 2026 12:00:00', 'event' => 'order.imported', 'actor' => 'Unknown: Imported from the previous shop system.', 'subject' => null, 'details' => null],
            ['occurred_at' => 'Oct 29, 2026 11:00:00', 'event' => 'inventory.reconciled', 'actor' => 'System: inventory-reconciliation', 'subject' => null, 'details' => null],
            ['occurred_at' => 'Oct 29, 2026 10:00:00', 'event' => 'product_review.deleted', 'actor' => 'API token: ERP sync', 'subject' => 'product_review #7', 'details' => 'rating: 4, status: {"from":"pending","to":"approved"}'],
            ['occurred_at' => 'Oct 29, 2026 09:00:00', 'event' => 'api_token.created', 'actor' => 'Admin: Ada Lovelace', 'subject' => 'api_token #'.$apiToken->id, 'details' => 'name: ERP sync'],
        ]);
});

it('names actors that were deleted by their ID', function () {
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);
    recordAt('2026-10-29 09:00:00', 'api_token.created', $alan);
    $alan->delete();

    expect(auditLogPage()->toArray()['props']['auditLog']['rows'][1]['actor'])->toBe('Admin #'.$alan->id);
});

it('shows times in the app\'s time zone, and says which', function () {
    config(['app.timezone' => 'Europe/Berlin']);
    recordAt('2026-10-29 09:00:00', 'api_token.created', $this->ada);

    auditLogPage()
        ->where('auditLog.columns.0.label', 'Time (Europe/Berlin)')
        ->where('auditLog.rows.1.occurred_at', 'Oct 29, 2026 10:00:00');
});

it('filters by event, actor, subject and day', function (array $filters, array $events) {
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);
    recordAt('2026-10-27 23:30:00', 'api_token.created', $this->ada, new Subject('api_token', 1));
    recordAt('2026-10-28 08:00:00', 'api_token.revoked', $this->ada, new Subject('api_token', 1));
    recordAt('2026-10-28 23:59:59', 'api_token.created', $alan, new Subject('api_token', 2));
    recordAt('2026-10-29 00:00:00', 'product_review.approved', new SystemActor('spam-filter'), new Subject('product_review', 1));

    expect(listedEvents($filters))->toBe($events);
})->with([
    'an event' => [['event' => 'api_token.created'], ['api_token.created', 'api_token.created']],
    'an actor type' => [['actor_type' => 'system'], ['product_review.approved']],
    'one admin' => [fn () => ['actor_type' => 'admin', 'actor_id' => (string) test()->ada->id], ['api_token.revoked', 'api_token.created']],
    'a subject type' => [['subject_type' => 'product_review'], ['product_review.approved']],
    'one subject' => [['subject_type' => 'api_token', 'subject_id' => '1'], ['api_token.revoked', 'api_token.created']],
    // Whole days, from the start of the first to the end of the last.
    'a day' => [['from' => '2026-10-28', 'until' => '2026-10-28'], ['api_token.created', 'api_token.revoked']],
    'from a day on' => [['from' => '2026-10-29'], ['product_review.approved']],
    'combined' => [['event' => 'api_token.created', 'until' => '2026-10-27'], ['api_token.created']],
]);

it('filters by days in the app\'s time zone', function () {
    config(['app.timezone' => 'Europe/Berlin']);
    // 23:30 UTC on Oct 27 is already Oct 28 in Berlin.
    recordAt('2026-10-27 23:30:00', 'api_token.created', $this->ada);

    expect(listedEvents(['from' => '2026-10-28', 'until' => '2026-10-28']))->toBe(['api_token.created'])
        ->and(listedEvents(['until' => '2026-10-27']))->toBe([]);
});

it('offers the events, actor types and subject types the log has', function () {
    recordAt('2026-10-28 08:00:00', 'api_token.revoked', $this->ada, new Subject('api_token', 1));
    recordAt('2026-10-28 09:00:00', 'product_review.approved', new SystemActor('spam-filter'), new Subject('product_review', 1));

    auditLogPage(['event' => 'api_token.revoked'])
        ->where('filters', ['event' => 'api_token.revoked', 'actor_type' => null, 'actor_id' => null, 'subject_type' => null, 'subject_id' => null, 'from' => null, 'until' => null])
        // Its own access records are events of the log too.
        ->where('eventOptions', ['api_token.revoked', 'audit_log.searched', 'product_review.approved'])
        ->where('actorTypeOptions', ['admin', 'system'])
        ->where('subjectTypeOptions', ['api_token', 'product_review']);
});

it('rejects filters it can\'t apply', function (array $filters, string $error) {
    $this->actingAs($this->ada, 'larasell-admin')
        ->from(route('larasell.admin.home'))
        ->get(route('larasell.admin.settings.audit-log.index', $filters))
        ->assertRedirect(route('larasell.admin.home'))
        ->assertSessionHasErrors($error);
})->with([
    'a date that is not one' => [['from' => 'yesterday'], 'from'],
    'an end before the start' => [['from' => '2026-10-29', 'until' => '2026-10-28'], 'until'],
    'an actor ID without its type' => [['actor_id' => '1'], 'actor_type'],
    'a subject ID without its type' => [['subject_id' => '1'], 'subject_type'],
]);

it('keeps the filters when paging', function () {
    foreach (range(1, 30) as $minute) {
        recordAt('2026-10-29 09:'.str_pad((string) $minute, 2, '0', STR_PAD_LEFT).':00', 'api_token.created', $this->ada);
    }
    recordAt('2026-10-29 10:00:00', 'api_token.revoked', $this->ada);

    auditLogPage(['event' => 'api_token.created', 'page' => '2'])
        ->where('auditLog.pagination', ['page' => 2, 'pageSize' => 25, 'total' => 30])
        ->where('auditLog.rows.0.occurred_at', 'Oct 29, 2026 09:05:00');
});

it('audits viewing the log, as the admin who did', function () {
    auditLogPage();

    $record = DB::table('larasell_audit_log')->sole();

    expect($record)->toMatchArray([
        'event' => 'audit_log.viewed',
        'category' => 'audit_access',
        'actor_type' => 'admin',
        'actor_id' => (string) $this->ada->id,
        'subject_type' => null,
        'source' => 'web',
    ])
        ->and(json_decode($record->properties, true))->toBe(['page' => 1]);
});

it('audits searching the log with the filters used', function () {
    auditLogPage(['event' => 'api_token.created', 'actor_type' => 'admin', 'actor_id' => '3', 'from' => '2026-10-01', 'page' => '2']);

    $record = DB::table('larasell_audit_log')->sole();

    expect($record->event)->toBe('audit_log.searched')
        ->and($record->category)->toBe('audit_access')
        ->and(json_decode($record->properties, true))->toBe([
            'filters' => ['event' => 'api_token.created', 'actor_type' => 'admin', 'actor_id' => '3', 'from' => '2026-10-01'],
            'page' => 2,
        ]);
});

it('shows nothing when its access can\'t be audited', function () {
    Schema::drop('larasell_audit_log');

    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.audit-log.index'))
        ->assertServerError();
});

it('adds the audit log to the settings navigation, after API tokens', function () {
    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => collect(collect($items)->firstWhere('label', 'Settings')['children'])->pluck('label')->all() === ['Admins', 'API tokens', 'Audit log']));
});

it('puts the audit log after Admins when API tokens are turned off', function () {
    $this->bootingWith(fn () => config(['larasell.admin.api_tokens.enabled' => false]));
    $this->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => collect(collect($items)->firstWhere('label', 'Settings')['children'])->pluck('label')->all() === ['Admins', 'Audit log']));
});

it('lets the viewAuditLog gate decide who may see the log', function () {
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);
    Gate::define('viewAuditLog', fn (Admin $admin): bool => $admin->is($this->ada));

    $this->actingAs($alan, 'larasell-admin')->get(route('larasell.admin.settings.audit-log.index'))->assertForbidden();
    $this->actingAs($this->ada, 'larasell-admin')->get(route('larasell.admin.settings.audit-log.index'))->assertOk();
});

it('lets the searchAuditLog gate decide who may filter the log', function () {
    Gate::define('searchAuditLog', fn (): bool => false);

    $this->actingAs($this->ada, 'larasell-admin');
    $this->get(route('larasell.admin.settings.audit-log.index', ['event' => 'api_token.created']))->assertForbidden();

    auditLogPage()->where('canSearch', false);

    expect(DB::table('larasell_audit_log')->pluck('event')->all())->toBe(['audit_log.viewed']);
});

it('keeps the audit log out of reach for guests', function () {
    $this->get(route('larasell.admin.settings.audit-log.index'))->assertRedirect(route('larasell.admin.login'));

    expect(DB::table('larasell_audit_log')->count())->toBe(0);
});
