<?php

namespace Larasell\Admin\AuditRecords\AuditLog;

use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;

/**
 * Someone opened a page of the audit log without filters.
 *
 * @internal Recorded by AuditLogController. Its event name, audit_log.viewed, is stable.
 */
class AuditLogViewed extends AuditRecord
{
    public function __construct(private readonly int $page) {}

    public function event(): string
    {
        return 'audit_log.viewed';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::AuditAccess;
    }

    public function subject(): ?Subject
    {
        return null;
    }

    public function properties(): array
    {
        return ['page' => $this->page];
    }
}
