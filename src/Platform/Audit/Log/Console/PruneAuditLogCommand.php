<?php

namespace Larasell\Admin\Platform\Audit\Log\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Audit\Actors\SystemActor;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\Chain\AuditChain;
use Larasell\Admin\Platform\Audit\Export\Jobs\DeleteExpiredAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Jobs\ExportAuditLog;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;
use Larasell\Admin\Platform\Audit\Log\AuditRecords\AuditLogPruned;
use Throwable;

/**
 * Deletes the oldest audit records, as long as they're older than the retention period set in
 * config/larasell/admin/audit.php, and records that it ran. Also deletes expired exports.
 *
 * Only ever the start of the chain: it stops at the first record still in the period, so an older
 * record behind it is kept until that one goes. A gap anywhere else is tampering.
 */
class PruneAuditLogCommand extends Command
{
    protected $signature = 'larasell:prune-audit-log';

    protected $description = 'Delete the oldest audit records once they are older than the retention period.';

    public function handle(Auditor $auditor, AuditChain $chain): int
    {
        $days = config('larasell.admin.audit.retention.days');

        if ($days === '') {
            $days = null;
        }

        if ($days !== null && ! (is_int($days) && $days > 0 || is_string($days) && preg_match('/^[1-9][0-9]*$/', $days) === 1)) {
            $this->components->error('larasell.admin.audit.retention.days must be a whole number of days above 0, or null to keep records forever.');

            return self::FAILURE;
        }

        $days = $days === null ? null : (int) $days;

        $legalHold = (bool) config('larasell.admin.audit.legal_hold');

        $deleted = DB::transaction(function () use ($auditor, $chain, $days, $legalHold): int {
            $deleted = 0;

            if (! $legalHold && $days !== null) {
                $cutoff = Date::now()->utc()->subDays($days)->format('Y-m-d H:i:s.u');
                $firstKept = DB::table(AuditChain::TABLE)->where('occurred_at', '>=', $cutoff)->min('seq');

                $deleted = DB::table(AuditChain::TABLE)
                    ->when($firstKept !== null, fn ($query) => $query->where('seq', '<', $firstKept))
                    ->delete();
            }

            $auditor->record(new AuditLogPruned($legalHold, $days, $deleted, $chain->startsAfter()), new SystemActor('larasell:prune-audit-log'));

            return $deleted;
        });

        // After the log, so a failing export can't keep the log from being pruned.
        $this->cleanUpExports();

        if ($legalHold) {
            $this->components->warn('No audit records were deleted, since they are under a legal hold.');
        } elseif ($days === null) {
            $this->components->info('No retention period is set in config/larasell/admin/audit.php, so every audit record was kept.');
        } else {
            $this->components->info("{$deleted} audit records deleted.");
        }

        return self::SUCCESS;
    }

    /**
     * Deletes expired exports, whatever the retention period, since they are copies, not the log,
     * and fails exports that never finished, e.g. because their worker died.
     */
    private function cleanUpExports(): void
    {
        AuditLogExport::query()->where('expires_at', '<=', Date::now()->utc())->each(function (AuditLogExport $export): void {
            try {
                DeleteExpiredAuditLogExport::delete($export);
            } catch (Throwable $exception) {
                $this->components->warn("Export {$export->id} wasn't deleted: {$exception->getMessage()}");
            }
        });

        AuditLogExport::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', Date::now()->utc()->subHour())
            ->each(fn (AuditLogExport $export) => ExportAuditLog::markFailed($export, "The export didn't finish within an hour."));
    }
}
