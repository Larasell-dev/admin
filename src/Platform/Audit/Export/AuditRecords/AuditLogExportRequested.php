<?php

namespace Larasell\Admin\Platform\Audit\Export\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;

/**
 * Someone started an export of the audit log and the format.
 *
 * @internal Recorded by AuditLogExportController. Its event name, audit_log.export_requested, is stable.
 */
class AuditLogExportRequested extends AuditRecord
{
    public function __construct(private readonly int $exportId, private readonly string $format) {}

    public function event(): string
    {
        return 'audit_log.export_requested';
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
        return ['format' => $this->format];
    }
}
