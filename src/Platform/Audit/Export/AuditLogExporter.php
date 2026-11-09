<?php

namespace Larasell\Admin\Platform\Audit\Export;

use Illuminate\Support\Facades\Date;
use Larasell\Admin\Platform\Audit\Chain\AuditChain;
use Larasell\Admin\Platform\Audit\Export\Exceptions\AuditLogExportUnwritable;
use Larasell\Admin\Platform\Audit\Export\Formats\AuditLogExportFormat;
use Larasell\Admin\Platform\Audit\Log\Models\AuditLogEntry;
use ZipArchive;

/**
 * Writes the whole audit log to a zip archive, as one file in the bound AuditLogExportFormat, then a
 * summary with the chain's head and whether the whole chain verified, so whoever keeps the export
 * can check it against the log later.
 *
 * @internal Used by the ExportAuditLog job and larasell:export-audit-log.
 */
class AuditLogExporter
{
    public function __construct(private readonly AuditChain $chain, public readonly AuditLogExportFormat $format) {}

    /**
     * Writes the zip archive to the path, and returns how many records it wrote.
     */
    public function export(string $path): int
    {
        // Written to disk first, since a zip archive is only finished once its file is complete.
        $file = (string) tempnam(sys_get_temp_dir(), 'larasell-audit-export');
        $stream = fopen($file, 'wb');

        if ($stream === false) {
            throw AuditLogExportUnwritable::cannotOpen($path);
        }

        $records = 0;
        $first = null;
        $last = null;

        try {
            $this->format->writeStart($stream);
            $query = AuditLogEntry::query()->toBase()->orderBy('seq');

            foreach ($query->lazyById(1000, 'seq') as $row) {
                // An unreadable record is written as stored; the summary says the chain broke there.
                $record = $this->chain->readable($row) ? $this->chain->normalize($row) : (array) $row;
                $record['seq'] = (int) $record['seq'];
                $this->format->writeRecord($stream, $record);
                $first ??= $record['seq'];
                $last = $record['seq'];
                $records++;
            }

            $verification = $this->chain->verify();

            $this->format->writeSummary($stream, [
                'exported_at' => Date::now()->utc()->format('Y-m-d H:i:s.u'),
                'records' => $records,
                'first_seq' => $first,
                'last_seq' => $last,
                'head' => $this->chain->head(),
                'verification' => ['passed' => $verification->passed, 'seq' => $verification->seq, 'reason' => $verification->reason],
            ]);
            fclose($stream);
            $this->zip($file, $path);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            @unlink($file);
        }

        return $records;
    }

    private function zip(string $file, string $path): void
    {
        $archive = new ZipArchive;

        $written = $archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true
            && $archive->addFile($file, 'audit-log.'.$this->format->extension())
            && $archive->close();

        if ($written) {
            return;
        }

        throw AuditLogExportUnwritable::cannotZip($path);
    }
}
