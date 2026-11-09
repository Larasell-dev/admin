<?php

namespace Larasell\Admin\Platform\Audit\Log\Actions;

use Larasell\Admin\Platform\Audit\Log\RetentionPeriod;

/**
 * How to prune: the retention period, or null to keep records forever, and whether a legal hold
 * keeps every record.
 */
final readonly class PruneAuditLogPayload
{
    public function __construct(public ?RetentionPeriod $retention, public bool $legalHold) {}
}
