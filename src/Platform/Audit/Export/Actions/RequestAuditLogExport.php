<?php

namespace Larasell\Admin\Platform\Audit\Export\Actions;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExportRequested;
use Larasell\Admin\Platform\Audit\Export\Enums\AuditLogExportStatus;
use Larasell\Admin\Platform\Audit\Export\Formats\AuditLogExportFormat;
use Larasell\Admin\Platform\Audit\Export\Jobs\DeleteExpiredAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Jobs\ExportAuditLog;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;
use Larasell\Larasell\Audit\Auditor;
use Larasell\Larasell\Audit\Origin;

/**
 * Stores a pending export with its record, then queues the job that writes it and the one that
 * deletes it once expired.
 *
 * @internal Used by AuditLogExportController.
 */
class RequestAuditLogExport
{
    /**
     * How long an export can be downloaded before it is deleted.
     */
    public const int HOURS_KEPT = 24;

    public function __construct(private readonly Auditor $auditor, private readonly AuditLogExportFormat $format) {}

    public function handle(RequestAuditLogExportPayload $payload, Origin $origin): AuditLogExport
    {
        // The export and its record are stored together, or neither is.
        $export = DB::transaction(function () use ($origin, $payload): AuditLogExport {
            $export = AuditLogExport::query()->create([
                'admin_id' => $payload->adminId,
                'format' => $this->format->extension(),
                'status' => AuditLogExportStatus::Pending,
                'created_at' => Date::now()->utc(),
                'expires_at' => Date::now()->utc()->addHours(self::HOURS_KEPT),
            ]);

            $this->auditor->record(new AuditLogExportRequested($export->id, $export->format), $origin);

            return $export;
        });

        // After the commit, so the job finds the export and the chain includes its request.
        ExportAuditLog::dispatch($export->id);
        // Deletes it once expired, even where no one scheduled larasell:prune-audit-log.
        DeleteExpiredAuditLogExport::dispatch($export->id)->delay($export->expires_at);

        return $export;
    }
}
