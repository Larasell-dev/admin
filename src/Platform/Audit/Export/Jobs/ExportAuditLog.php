<?php

namespace Larasell\Admin\Platform\Audit\Export\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Platform\Audit\Export\AuditLogExporter;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExportFailed;
use Larasell\Admin\Platform\Audit\Export\Enums\AuditLogExportStatus;
use Larasell\Admin\Platform\Audit\Export\Events\AuditLogExportFinished;
use Larasell\Admin\Platform\Audit\Export\Exceptions\InvalidAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;
use Larasell\Admin\Platform\Support\Assert;
use Larasell\Larasell\Audit\Actors\SystemActor;
use Larasell\Larasell\Audit\Auditor;
use Larasell\Larasell\Audit\Origin;
use Throwable;

/**
 * Writes an export an admin asked for to the export disk, on the queue, so a large log takes no
 * request's time or memory.
 *
 * @internal Dispatched by AuditLogExportController.
 */
class ExportAuditLog implements ShouldQueue
{
    use Queueable;

    /** Run once: a second run would write the same export again. */
    public int $tries = 1;

    /** A hung export is stopped and marked failed rather than left pending. */
    public int $timeout = 600;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $exportId) {}

    public function handle(AuditLogExporter $exporter): void
    {
        // Claimed with a conditional update, so a job the queue runs twice writes it only once.
        $claimed = AuditLogExport::query()->whereKey($this->exportId)->whereNull('started_at')->update(['started_at' => Date::now()->utc()]);

        if ($claimed === 0) {
            return;
        }

        $export = AuditLogExport::query()->findOrFail($this->exportId);

        // Only a pending export is claimed; prune marks one failed only after an hour, long past the timeout.
        if (! Assert::that($export->status === AuditLogExportStatus::Pending, fn () => InvalidAuditLogExport::notPending($export->id, $export->status->value))) {
            return;
        }
        $file = tempnam(sys_get_temp_dir(), 'larasell-audit-export');

        try {
            $records = $exporter->export($file);
            // Streamed to the disk, so a large export never sits in memory.
            $path = Storage::disk(config('larasell.admin.audit.export.disk'))
                ->putFileAs(DeleteExpiredAuditLogExport::FOLDER, new File($file), "{$export->id}.zip");

            $export->update(['status' => AuditLogExportStatus::Ready, 'path' => $path, 'records' => $records]);
            AuditLogExportFinished::dispatch($export->admin_id);
        } finally {
            @unlink($file);
        }
    }

    public function failed(Throwable $exception): void
    {
        $export = AuditLogExport::query()->find($this->exportId);

        // Something after the export was written can fail too, e.g. the broadcast; it stays ready then.
        if ($export?->status === AuditLogExportStatus::Pending) {
            self::markFailed($export, $exception->getMessage());
        }
    }

    /**
     * @internal Also called when the audit log is pruned, for exports that never finished.
     */
    public static function markFailed(AuditLogExport $export, string $error): void
    {
        if (! Assert::that($export->status === AuditLogExportStatus::Pending, fn () => InvalidAuditLogExport::notPending($export->id, $export->status->value))) {
            return;
        }

        DB::transaction(function () use ($export, $error): void {
            $export->update(['status' => AuditLogExportStatus::Failed]);
            app(Auditor::class)->record(new AuditLogExportFailed($export->id, $export->format, $error), Origin::job(new SystemActor('audit-log-export')));
        });
        AuditLogExportFinished::dispatch($export->admin_id);
    }
}
