<?php

declare(strict_types=1);

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExportDeleted;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExported;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExportFailed;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExportRequested;
use Larasell\Admin\Platform\Audit\Export\Enums\AuditLogExportStatus;
use Larasell\Admin\Platform\Audit\Export\Events\AuditLogExportFinished;
use Larasell\Admin\Platform\Audit\Export\Exceptions\InvalidAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Formats\AuditLogExportFormat;
use Larasell\Admin\Platform\Audit\Export\Jobs\DeleteExpiredAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Jobs\ExportAuditLog;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Audit\Actors\SystemActor;
use Larasell\Larasell\Audit\Auditor;
use Larasell\Larasell\Audit\AuditRecord;
use Larasell\Larasell\Audit\Enums\AuditCategory;
use Larasell\Larasell\Audit\Exceptions\AuditFailed;
use Larasell\Larasell\Audit\Facades\Audit;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Audit\Subject;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));
    Storage::fake('local');
    config(['queue.default' => 'sync']);

    $this->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $this->alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);
});

function catalogued(string $event, int $book): void
{
    DB::transaction(fn () => app(Auditor::class)->record(new class($event, $book) extends AuditRecord
    {
        public function __construct(private readonly string $name, private readonly int $book) {}

        public function event(): string
        {
            return $this->name;
        }

        public function category(): AuditCategory
        {
            return AuditCategory::BusinessCritical;
        }

        public function subject(): ?Subject
        {
            return new Subject('book', $this->book);
        }

        public function properties(): array
        {
            return ['shelf' => $this->book];
        }
    }, Origin::cli(new SystemActor('librarian'))));
}

/**
 * Starts an export as the admin, and returns the page's exports after it.
 *
 * @return list<array<string, mixed>>
 */
function exportAs(Admin $admin): array
{
    test()->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.settings.audit-log.exports.store'))
        ->assertRedirect(route('larasell.admin.settings.audit-log.index'));

    $exports = null;
    test()->get(route('larasell.admin.settings.audit-log.index'))
        ->assertInertia(function (AssertableInertia $page) use (&$exports) {
            $exports = $page->toArray()['props']['exports'];
        });

    return $exports;
}

/**
 * The names of the files in the zip archive, and the content of the one it should hold.
 *
 * @return array{list<string>, string}
 */
function unzipped(string $zip, string $name): array
{
    $archive = new ZipArchive;
    expect($archive->open($zip))->toBeTrue();

    $names = array_map(fn (int $i) => $archive->getNameIndex($i), range(0, $archive->numFiles - 1));
    $content = $archive->getFromName($name);
    $archive->close();

    return [$names, (string) $content];
}

/**
 * The JSON document in the export's zip archive.
 *
 * @return array{records: list<array<string, mixed>>, summary: array<string, mixed>}
 */
function exported(string $zip): array
{
    [$names, $content] = unzipped($zip, 'audit-log.json');

    expect($names)->toBe(['audit-log.json']);

    return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
}

function storedExport(): string
{
    return Storage::disk('local')->path(exportPath());
}

function exportPath(): string
{
    return DB::table('larasell_audit_log_exports')->sole()->path;
}

it('exports every record as a JSON file in a zip archive, with its place in the chain and a summary', function () {
    // Arrange
    catalogued('book.shelved', 1);
    catalogued('book.lent', 2);

    // Act
    $exports = exportAs($this->ada);

    // Assert
    $export = exported(storedExport());
    $records = $export['records'];
    // The export's own export_requested record is the third.
    $stored = DB::table('larasell_audit_log')->where('seq', '<=', 3)->orderBy('seq')->get();

    expect($exports)->toHaveCount(1)
        ->and($exports[0])->toMatchArray(['status' => 'ready', 'records' => 3])
        ->and($exports[0]['downloadUrl'])->toContain('signature=')
        ->and(exportPath())->toEndWith('.zip')
        ->and(array_column($records, 'seq'))->toBe([1, 2, 3])
        ->and(array_column($records, 'hash'))->toBe($stored->pluck('hash')->all())
        ->and($records[1]['previous_hash'])->toBe($records[0]['hash'])
        ->and($records[0])->toMatchArray(['event' => 'book.shelved', 'subject_id' => '1', 'properties' => ['shelf' => 1], 'occurred_at' => '2026-10-30 12:00:00.000000'])
        ->and(array_keys($export))->toBe(['records', 'summary'])
        ->and($export['summary'])->toBe([
            'exported_at' => '2026-10-30 12:00:00.000000',
            'records' => 3,
            'first_seq' => 1,
            'last_seq' => 3,
            'head' => ['seq' => 3, 'sha256' => $stored[2]->hash],
            'verification' => ['passed' => true, 'seq' => 3, 'reason' => null],
        ]);
});

it('records starting an export, as the admin who did', function () {
    // Arrange
    Audit::fake([AuditLogExportRequested::class]);

    // Act
    exportAs($this->ada);

    // Assert
    Audit::assertRecorded(AuditLogExportRequested::class, fn (AuditLogExportRequested $record, Origin $origin) => $origin->actor instanceof Admin && $origin->actor->is($this->ada)
        && $record->properties() === ['format' => 'json']);
});

it('says in the summary when the chain is broken', function () {
    // Arrange
    catalogued('book.shelved', 1);
    catalogued('book.lent', 2);
    DB::table('larasell_audit_log')->where('seq', 1)->update(['event' => 'book.burned']);

    // Act
    exportAs($this->ada);

    // Assert
    expect(exported(storedExport())['summary']['verification'])->toBe(['passed' => false, 'seq' => 1, 'reason' => 'hash_mismatch']);
});

it('lets the admin who exported download it, and records the download', function () {
    // Arrange
    Audit::fake([AuditLogExported::class]);
    catalogued('book.shelved', 1);
    $url = exportAs($this->ada)[0]['downloadUrl'];

    // Act
    $response = $this->get($url);

    // Assert
    $response->assertOk()->assertDownload('audit-log-2026-10-30-120000.zip');

    Audit::assertRecorded(AuditLogExported::class, fn (AuditLogExported $record, Origin $origin) => $origin->actor instanceof Admin && $origin->actor->is($this->ada)
        && $record->subject()?->type === 'audit_log_export'
        && $record->properties() === ['format' => 'json', 'record_count' => 2]);
});

it('keeps the download from other admins, unsigned links and expired exports', function () {
    // Arrange
    Audit::fake([AuditLogExported::class]);
    $url = exportAs($this->ada)[0]['downloadUrl'];

    // Act & Assert
    $this->actingAs($this->alan, 'larasell-admin')->get($url)->assertForbidden();
    $this->actingAs($this->ada, 'larasell-admin')->get(strtok($url, '?'))->assertForbidden();

    $this->travel(25)->hours();
    $this->get($url)->assertForbidden();

    Audit::assertNotRecorded(AuditLogExported::class);
});

it('shows only the admin\'s own exports', function () {
    // Arrange
    exportAs($this->alan);

    // Act & Assert
    expect(exportAs($this->ada))->toHaveCount(1);
});

it('lets the larasell.admin.export-audit-log gate decide who may export', function () {
    // Arrange
    Gate::define('larasell.admin.export-audit-log', fn (Admin $admin) => $admin->is($this->alan));

    // Act & Assert
    $this->actingAs($this->ada, 'larasell-admin')
        ->post(route('larasell.admin.settings.audit-log.exports.store'))
        ->assertForbidden();

    $this->get(route('larasell.admin.settings.audit-log.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('exportUrl', null));

    expect(DB::table('larasell_audit_log_exports')->count())->toBe(0);
});

it('writes the file in the format bound to AuditLogExportFormat', function () {
    // Arrange
    catalogued('book.shelved', 1);
    app()->bind(AuditLogExportFormat::class, fn () => new class implements AuditLogExportFormat
    {
        public function extension(): string
        {
            return 'csv';
        }

        public function contentType(): string
        {
            return 'text/csv';
        }

        public function writeStart($stream): void
        {
            fwrite($stream, "seq,event\n");
        }

        public function writeRecord($stream, array $record): void
        {
            fwrite($stream, $record['seq'].','.$record['event']."\n");
        }

        public function writeSummary($stream, array $summary): void
        {
            fwrite($stream, 'records,'.$summary['records']."\n");
        }
    });

    // Act
    exportAs($this->ada);

    // Assert
    expect(exportPath())->toEndWith('.zip')
        ->and(unzipped(storedExport(), 'audit-log.csv'))->toBe([['audit-log.csv'], "seq,event\n1,book.shelved\n2,audit_log.export_requested\nrecords,2\n"]);
});

it('deletes exports and their files once they expire, when the log is pruned', function () {
    // Arrange
    exportAs($this->ada);
    $path = exportPath();
    $this->travel(23)->hours();
    exportAs($this->ada);

    // Act
    $this->travel(2)->hours();
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    // Assert
    expect(DB::table('larasell_audit_log_exports')->count())->toBe(1)
        ->and(Storage::disk('local')->exists($path))->toBeFalse();
});

it('exports on the command line to a file, recorded as the command', function () {
    // Arrange
    Audit::fake([AuditLogExported::class]);
    catalogued('book.shelved', 1);
    $file = tempnam(sys_get_temp_dir(), 'audit').'.zip';

    try {
        // Act
        $this->artisan('larasell:export-audit-log', ['path' => $file])
            ->expectsOutputToContain('1 audit records exported')
            ->assertSuccessful();

        // Assert
        $export = exported($file);

        expect($export['records'][0]['event'])->toBe('book.shelved')
            ->and($export['summary']['records'])->toBe(1);
        Audit::assertRecorded(AuditLogExported::class, fn (AuditLogExported $record, Origin $origin) => $origin->actor == new SystemActor('larasell:export-audit-log'));
    } finally {
        @unlink($file);
    }
});

it('answers before the export is written, which a queued job does', function () {
    // Arrange
    Queue::fake();

    // Act
    $exports = exportAs($this->ada);

    // Assert
    Queue::assertPushed(ExportAuditLog::class);

    expect($exports)->toHaveCount(1)
        ->and($exports[0])->toMatchArray(['status' => 'pending', 'records' => null, 'downloadUrl' => null]);
});

it('tells the admin\'s browsers when their export is ready, or failed', function () {
    // Arrange
    Event::fake([AuditLogExportFinished::class]);
    exportAs($this->ada);
    $failing = AuditLogExport::query()->create(['admin_id' => $this->alan->id, 'format' => 'json', 'status' => 'pending', 'created_at' => now(), 'expires_at' => now()->addDay()]);

    // Act
    (new ExportAuditLog($failing->id))->failed(new RuntimeException('Disk full'));

    // Assert
    Event::assertDispatched(AuditLogExportFinished::class, 2);
    Event::assertDispatched(AuditLogExportFinished::class, fn (AuditLogExportFinished $event) => $event->broadcastOn() == new PrivateChannel('larasell-admin.audit-log-exports.'.$this->ada->id));
    Event::assertDispatched(AuditLogExportFinished::class, fn (AuditLogExportFinished $event) => $event->broadcastOn() == new PrivateChannel('larasell-admin.audit-log-exports.'.$this->alan->id));

    expect($failing->fresh()->status)->toBe(AuditLogExportStatus::Failed);
});

it('reloads the exports live, on the admin\'s own channel', function () {
    // Act & Assert
    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.audit-log.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('exports'));

    $live = $this->get(route('larasell.admin.settings.audit-log.index'))->viewData('page')['liveProps'] ?? null;

    expect(json_encode($live))->toContain('larasell-admin.audit-log-exports.'.$this->ada->id)
        ->toContain('audit-log-export.finished');
});

it('lets only the admin listen on their exports\' channel', function () {
    // Arrange
    $channels = app(BroadcastManager::class)->driver()->getChannels();
    $authorize = $channels->get('larasell-admin.audit-log-exports.{adminId}');

    // Act & Assert
    expect($authorize($this->ada, (string) $this->ada->id))->toBeTrue()
        ->and($authorize($this->alan, (string) $this->ada->id))->toBeFalse();
});

it('records no download when the file can\'t be read', function () {
    // Arrange
    Audit::fake([AuditLogExported::class]);
    $url = exportAs($this->ada)[0]['downloadUrl'];
    Storage::disk('local')->delete(exportPath());

    // Act
    $response = $this->get($url);

    // Assert
    $response->assertNotFound();

    Audit::assertNotRecorded(AuditLogExported::class);
});

it('records a failed export, with why it failed', function () {
    // Arrange
    Audit::fake([AuditLogExportFailed::class]);
    Queue::fake();
    exportAs($this->ada);
    $export = AuditLogExport::query()->sole();

    // Act
    (new ExportAuditLog($export->id))->failed(new RuntimeException('Disk full'));

    // Assert
    Audit::assertRecorded(AuditLogExportFailed::class, fn (AuditLogExportFailed $record, Origin $origin) => $origin->actor instanceof SystemActor
        && $record->subject() == new Subject('audit_log_export', $export->id)
        && $record->properties() === ['format' => 'json', 'error' => 'Disk full']);
});

it('writes no file on the command line when its record can\'t be stored', function () {
    // Arrange
    $file = sys_get_temp_dir().'/audit-'.uniqid().'.zip';
    Audit::fake()->failing();

    // Act & Assert
    expect(fn () => $this->artisan('larasell:export-audit-log', ['path' => $file])->run())->toThrow(AuditFailed::class);

    expect(file_exists($file))->toBeFalse();
});

it('exports a log with an unreadable record, and says where the chain broke', function () {
    // Arrange
    catalogued('book.shelved', 1);
    DB::table('larasell_audit_log')->where('seq', 1)->update(['properties' => 'not json']);

    // Act
    exportAs($this->ada);

    // Assert
    $export = exported(storedExport());

    expect($export['records'][0]['seq'])->toBe(1)
        ->and($export['summary']['verification'])->toBe(['passed' => false, 'seq' => 1, 'reason' => 'unreadable_record']);
});

it('prunes the log even when deleting an expired export fails, and keeps that export\'s file', function () {
    // Arrange
    config(['larasell.audit.retention.days' => 30]);
    Exceptions::fake();
    $this->travelTo(now()->subDays(40));
    catalogued('book.shelved', 1);
    $this->travelBack();
    $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));
    exportAs($this->ada);
    $path = exportPath();
    $this->travel(25)->hours();
    AuditLogExport::deleting(fn () => throw new RuntimeException('Database timeout'));

    // Act
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    // Assert
    expect(DB::table('larasell_audit_log')->where('event', 'book.shelved')->exists())->toBeFalse()
        ->and(Storage::disk('local')->exists($path))->toBeTrue();
    Exceptions::assertReported(fn (RuntimeException $exception) => $exception->getMessage() === 'Database timeout');
});

it('deletes each export when it expires, without the prune command', function () {
    // Arrange
    Queue::fake();
    exportAs($this->ada);
    $export = AuditLogExport::query()->sole();
    Storage::disk('local')->put('larasell/audit-log-exports/'.$export->id.'.zip', 'zip');
    $export->update(['status' => 'ready', 'path' => 'larasell/audit-log-exports/'.$export->id.'.zip']);

    // Act
    $early = new DeleteExpiredAuditLogExport($export->id);
    $early->handle();
    $this->travel(25)->hours();
    (new DeleteExpiredAuditLogExport($export->id))->handle();

    // Assert
    Queue::assertPushed(DeleteExpiredAuditLogExport::class, fn ($job) => $job->delay->equalTo($export->expires_at));

    expect(AuditLogExport::query()->exists())->toBeFalse()
        ->and(Storage::disk('local')->exists($export->path))->toBeFalse();
});

it('writes an export only once, even when the job runs twice', function () {
    // Arrange
    Queue::fake();
    exportAs($this->ada);
    $export = AuditLogExport::query()->sole();
    $export->update(['started_at' => now()]);

    // Act
    app()->call([new ExportAuditLog($export->id), 'handle']);

    // Assert
    expect($export->fresh()->status)->toBe(AuditLogExportStatus::Pending)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('fails exports that never finished, when the log is pruned', function () {
    // Arrange
    Audit::fake([AuditLogExportFailed::class]);
    Queue::fake();
    Event::fake([AuditLogExportFinished::class]);
    exportAs($this->ada);
    $this->travel(61)->minutes();

    // Act
    $this->artisan('larasell:prune-audit-log')->assertSuccessful();

    // Assert
    expect(AuditLogExport::query()->sole()->status)->toBe(AuditLogExportStatus::Failed);
    Audit::assertRecorded(AuditLogExportFailed::class, fn (AuditLogExportFailed $record) => $record->properties() === ['format' => 'json', 'error' => 'The export didn\'t finish within an hour.']);
    Event::assertDispatched(AuditLogExportFinished::class);
});

it('gives up on an export that takes too long, instead of retrying it', function () {
    // Act
    $job = new ExportAuditLog(1);

    // Assert
    expect($job->tries)->toBe(1)->and($job->timeout)->toBe(600)->and($job->failOnTimeout)->toBeTrue();
});

it('lets the admin delete their export once they type the confirmation, and records it', function () {
    // Arrange
    Audit::fake([AuditLogExportDeleted::class]);
    $deleteUrl = exportAs($this->ada)[0]['deleteUrl'];
    $export = AuditLogExport::query()->sole();
    $path = $export->path;

    // Act & Assert
    $this->delete($deleteUrl, ['confirmation' => 'delete'])->assertSessionHasErrors('confirmation');
    expect(AuditLogExport::query()->whereKey($export->id)->exists())->toBeTrue();

    $this->delete($deleteUrl, ['confirmation' => 'Delete export'])->assertRedirect(route('larasell.admin.settings.audit-log.index'));

    expect(AuditLogExport::query()->whereKey($export->id)->exists())->toBeFalse()
        ->and(Storage::disk('local')->exists($path))->toBeFalse();
    Audit::assertRecorded(AuditLogExportDeleted::class, fn (AuditLogExportDeleted $record, Origin $origin) => $origin->actor instanceof Admin && $origin->actor->is($this->ada)
        && $record->subject() == new Subject('audit_log_export', $export->id));
});

it('keeps other admins from deleting an export', function () {
    // Arrange
    $deleteUrl = exportAs($this->ada)[0]['deleteUrl'];

    // Act & Assert
    $this->actingAs($this->alan, 'larasell-admin')->delete($deleteUrl, ['confirmation' => 'Delete export'])->assertForbidden();

    expect(AuditLogExport::query()->exists())->toBeTrue();
});

it('refuses to download a ready export without a file, instead of reading another one', function () {
    // Arrange
    Audit::fake([AuditLogExported::class]);
    $url = exportAs($this->ada)[0]['downloadUrl'];
    AuditLogExport::query()->sole()->update(['path' => null]);

    // Act & Assert
    expect(fn () => $this->withoutExceptionHandling()->get($url))->toThrow(InvalidAuditLogExport::class);

    Audit::assertNotRecorded(AuditLogExported::class);
});

it('keeps a ready export ready when its job reports a failure late', function () {
    // Arrange
    Audit::fake([AuditLogExportFailed::class]);
    exportAs($this->ada);
    $export = AuditLogExport::query()->sole();

    // Act
    (new ExportAuditLog($export->id))->failed(new RuntimeException('Broadcast failed'));

    // Assert
    expect($export->fresh()->status)->toBe(AuditLogExportStatus::Ready);
    Audit::assertNotRecorded(AuditLogExportFailed::class);
});

it('never deletes a file outside the exports folder, whatever the export\'s path says', function (string $path) {
    // Arrange
    Storage::disk('local')->put('secrets/keys.txt', 'secret');
    $export = AuditLogExport::query()->create(['admin_id' => $this->ada->id, 'format' => 'json', 'status' => AuditLogExportStatus::Ready, 'path' => $path, 'records' => 1, 'created_at' => now(), 'expires_at' => now()]);

    // Act & Assert
    expect(fn () => DeleteExpiredAuditLogExport::delete($export))->toThrow(InvalidAuditLogExport::class);

    expect(Storage::disk('local')->get('secrets/keys.txt'))->toBe('secret')
        ->and($export->fresh())->not->toBeNull();
})->with([
    'another folder' => ['secrets/keys.txt'],
    'out of the folder' => ['larasell/audit-log-exports/../../secrets/keys.txt'],
    'the folder itself' => ['larasell/audit-log-exports'],
]);
