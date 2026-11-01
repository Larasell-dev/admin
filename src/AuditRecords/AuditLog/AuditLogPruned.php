<?php

namespace Larasell\Admin\AuditRecords\AuditLog;

use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;

/**
 * The retention settings were applied: how long each category is kept, whether a legal hold was in
 * place, and how many records of each category were deleted.
 *
 * @internal Recorded by PruneAuditLogCommand. Its event name, audit_log.pruned, is stable.
 */
class AuditLogPruned extends AuditRecord
{
    /**
     * @param  array<string, ?int>  $retentionDays
     * @param  array<string, int>  $deleted
     */
    public function __construct(
        private readonly bool $legalHold,
        private readonly array $retentionDays,
        private readonly array $deleted,
    ) {}

    public function event(): string
    {
        return 'audit_log.pruned';
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
        return ['legal_hold' => $this->legalHold, 'retention_days' => $this->retentionDays, 'deleted' => $this->deleted];
    }
}
