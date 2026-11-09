<?php

namespace Larasell\Admin\Platform\Audit\Export\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;

/**
 * An export of the audit log left the app: an admin downloaded it, or the command wrote it to a file.
 *
 * @internal Recorded by AuditLogExportController and larasell:export-audit-log. Its event name, audit_log.exported, is stable.
 */
class AuditLogExported extends AuditRecord
{
    public function __construct(private readonly ?int $exportId, private readonly string $format, private readonly int $records) {}

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
        return ['format' => $this->format, 'records' => $this->records];
    }
}
