<?php

namespace Larasell\Admin\Platform\Audit\Export\AuditRecords;

use Larasell\Larasell\Audit\AuditRecord;
use Larasell\Larasell\Audit\Enums\AuditCategory;
use Larasell\Larasell\Audit\Subject;

/**
 * An export of the audit log couldn't be written, with why.
 *
 * @internal Recorded by the ExportAuditLog job. Its event name, audit_log.export_failed, is stable.
 */
class AuditLogExportFailed extends AuditRecord
{
    /**
     * @param  string  $format  The extension of the file in the zip archive, from the AuditLogExportFormat
     *                          that writes it, e.g. json. A string, since apps can bind a format of their own.
     */
    public function __construct(private readonly int $exportId, private readonly string $format, private readonly string $error) {}

    public function event(): string
    {
        return 'audit_log.export_failed';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::AuditAccess;
    }

    public function subject(): Subject
    {
        return new Subject('audit_log_export', $this->exportId);
    }

    public function properties(): array
    {
        return ['format' => $this->format, 'error' => $this->error];
    }
}
