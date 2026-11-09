<?php

namespace Larasell\Admin\Platform\Audit\Log\Console;

use Illuminate\Console\Command;
use Larasell\Admin\Platform\Audit\Log\Actions\PruneAuditLog;
use Larasell\Admin\Platform\Audit\Log\Actions\PruneAuditLogPayload;
use Larasell\Admin\Platform\Audit\Log\Exceptions\InvalidRetentionPeriod;
use Larasell\Admin\Platform\Audit\Log\RetentionPeriod;

/**
 * Prunes the audit log with the retention period and legal hold set in
 * config/larasell/admin/audit.php.
 */
class PruneAuditLogCommand extends Command
{
    protected $signature = 'larasell:prune-audit-log';

    protected $description = 'Delete the oldest audit records once they are older than the retention period.';

    public function handle(PruneAuditLog $prune): int
    {
        try {
            $retention = RetentionPeriod::fromConfig(config('larasell.admin.audit.retention.days'));
        } catch (InvalidRetentionPeriod $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $payload = new PruneAuditLogPayload($retention, (bool) config('larasell.admin.audit.legal_hold'));
        $result = $prune->handle($payload);

        foreach ($result->undeletedExports as $id => $error) {
            $this->components->warn("Export {$id} wasn't deleted: {$error}");
        }

        if ($payload->legalHold) {
            $this->components->warn('No audit records were deleted, since they are under a legal hold.');
        } elseif ($retention === null) {
            $this->components->info('No retention period is set in config/larasell/admin/audit.php, so every audit record was kept.');
        } else {
            $this->components->info("{$result->deleted} audit records deleted.");
        }

        return self::SUCCESS;
    }
}
