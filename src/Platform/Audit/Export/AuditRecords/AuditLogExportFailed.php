<?php

namespace Larasell\Admin\Platform\Audit\Export\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;

/**
 * An export of the audit log couldn't be written, with why.
 *
 * @internal Recorded by the ExportAuditLog job. Its event name, audit_log.export_failed, is stable.
 */
class AuditLogExportFailed extends AuditRecord
{
    public function __construct(private readonly string $exportId, private readonly string $format, private readonly string $error) {}

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
