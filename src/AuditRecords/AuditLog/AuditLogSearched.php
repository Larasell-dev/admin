<?php

namespace Larasell\Admin\AuditRecords\AuditLog;

use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;

/**
 * Someone opened a page of the audit log filtered, with the filters they used.
 *
 * @internal Recorded by AuditLogController. Its event name, audit_log.searched, is stable.
 */
class AuditLogSearched extends AuditRecord
{
    /**
     * @param  array<string, string>  $filters
     */
    public function __construct(private readonly array $filters, private readonly int $page) {}

    public function event(): string
    {
        return 'audit_log.searched';
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
        return ['filters' => $this->filters, 'page' => $this->page];
    }
}
