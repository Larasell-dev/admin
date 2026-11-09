<?php

namespace Larasell\Admin\Platform\Audit\Export\Actions;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExportDeleted;
use Larasell\Admin\Platform\Audit\Export\Events\AuditLogExportFinished;
use Larasell\Admin\Platform\Audit\Export\Jobs\DeleteExpiredAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;

/**
 * Deletes an export before it expires, with a record that it was deleted.
 *
 * @internal Used by AuditLogExportController.
 */
class DeleteAuditLogExport
{
    public function __construct(private readonly Auditor $auditor) {}

    public function handle(AuditLogExport $export): void
    {
        DB::transaction(function () use ($export): void {
            $this->auditor->record(new AuditLogExportDeleted($export->id, $export->format));
            DeleteExpiredAuditLogExport::delete($export);
        });

        AuditLogExportFinished::dispatch($export->admin_id);
    }
}
