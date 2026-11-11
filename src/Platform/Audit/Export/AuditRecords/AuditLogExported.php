<?php

namespace Larasell\Admin\Platform\Audit\Export\AuditRecords;

use Larasell\Larasell\Audit\AuditRecord;
use Larasell\Larasell\Audit\Enums\AuditCategory;
use Larasell\Larasell\Audit\Subject;

/**
 * An export of the audit log left the app: an admin downloaded it, or the command wrote it to a file.
 *
 * @internal Recorded by AuditLogExportController and larasell:export-audit-log. Its event name, audit_log.exported, is stable.
 */
class AuditLogExported extends AuditRecord
{
    /**
     * @param  string  $format  The extension of the file in the zip archive, from the AuditLogExportFormat
     *                          that writes it, e.g. json. A string, since apps can bind a format of their own.
     * @param  int  $recordCount  How many audit records the export holds.
     */
    public function __construct(private readonly ?int $exportId, private readonly string $format, private readonly int $recordCount) {}

    public function event(): string
    {
        return 'audit_log.exported';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::AuditAccess;
    }

    public function subject(): ?Subject
    {
        return $this->exportId === null ? null : new Subject('audit_log_export', $this->exportId);
    }

    public function properties(): array
    {
        return ['format' => $this->format, 'record_count' => $this->recordCount];
    }

    /**
     * A download or a file written changes nothing in the database, so there is nothing to commit with.
     */
    public function requiresTransaction(): bool
    {
        return false;
    }
}
