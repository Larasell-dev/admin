<?php

namespace Larasell\Admin\Platform\Audit\Export\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Audit\Actors\SystemActor;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\Export\AuditLogExporter;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExported;

/**
 * Exports the whole audit log to a file, e.g. nightly to storage the app's database can't reach,
 * so a rewritten log won't match the copy.
 */
class ExportAuditLogCommand extends Command
{
    protected $signature = 'larasell:export-audit-log {path : The zip archive to write, e.g. audit-log.zip}';

    protected $description = 'Export the audit log to a zip archive.';

    public function handle(Auditor $auditor, AuditLogExporter $exporter): int
    {
        $path = (string) $this->argument('path');

        // The file only stays when its record is stored, so no export leaves without the log saying so.
        try {
            $records = DB::transaction(function () use ($auditor, $exporter, $path): int {
                $records = $exporter->export($path);
                $auditor->record(new AuditLogExported(null, $exporter->format->extension(), $records), new SystemActor('larasell:export-audit-log'));

                return $records;
            });
        } catch (\Throwable $exception) {
            @unlink($path);

            throw $exception;
        }

        $this->components->info("{$records} audit records exported to {$this->argument('path')}.");

        return self::SUCCESS;
    }
}
