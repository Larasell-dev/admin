<?php

namespace Larasell\Admin\Platform\Audit\Export\Listeners;

use Illuminate\Support\Facades\Date;
use Larasell\Admin\Platform\Audit\Export\Enums\AuditLogExportStatus;
use Larasell\Admin\Platform\Audit\Export\Jobs\DeleteExpiredAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Jobs\ExportAuditLog;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;
use Larasell\Larasell\Audit\Events\AuditLogPruned;
use Throwable;

/**
 * Deletes expired exports whenever the core prunes the audit log, whatever the retention period,
 * since they are copies, not the log, and fails exports that never finished, e.g. because their
 * worker died. An export that can't be deleted is reported and kept for the next run.
 *
 * @internal Registered by AuditServiceProvider.
 */
class CleanUpAuditLogExports
{
    public function handle(AuditLogPruned $event): void
    {
        AuditLogExport::query()->where('expires_at', '<=', Date::now()->utc())->each(function (AuditLogExport $export): void {
            try {
                DeleteExpiredAuditLogExport::delete($export);
            } catch (Throwable $exception) {
                report($exception);
            }
        });

        AuditLogExport::query()
            ->where('status', AuditLogExportStatus::Pending)
            ->where('created_at', '<=', Date::now()->utc()->subHour())
            ->each(fn (AuditLogExport $export) => ExportAuditLog::markFailed($export, "The export didn't finish within an hour."));
    }
}
