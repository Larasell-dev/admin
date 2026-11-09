<?php

namespace Larasell\Admin\Platform\Audit\Log\Actions;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Audit\Actors\SystemActor;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\Chain\AuditChain;
use Larasell\Admin\Platform\Audit\Exceptions\AuditFailed;
use Larasell\Admin\Platform\Audit\Export\Enums\AuditLogExportStatus;
use Larasell\Admin\Platform\Audit\Export\Jobs\DeleteExpiredAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Jobs\ExportAuditLog;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;
use Larasell\Admin\Platform\Audit\Log\AuditRecords\AuditLogPruned;
use Throwable;

/**
 * Deletes the oldest audit records, as long as they're older than the retention period, and
 * records that it ran. Also deletes expired exports.
 *
 * Only ever the start of the chain: it stops at the first record still in the period, so an older
 * record behind it is kept until that one goes. A gap anywhere else is tampering.
 *
 * @internal Used by larasell:prune-audit-log.
 */
class PruneAuditLog
{
    public function __construct(private readonly Auditor $auditor, private readonly AuditChain $chain) {}

    public function handle(PruneAuditLogPayload $payload): PruneAuditLogResult
    {
        $deleted = DB::transaction(function () use ($payload): int {
            $deleted = 0;

            if ($payload->retention !== null && ! $payload->legalHold) {
                $cutoff = Date::now()->utc()->subDays($payload->retention->days)->format('Y-m-d H:i:s.u');
                $firstKept = DB::table(AuditChain::TABLE)->where('occurred_at', '>=', $cutoff)->min('seq');

                $doomed = DB::table(AuditChain::TABLE)->when($firstKept !== null, fn ($query) => $query->where('seq', '<', $firstKept));
                $from = (int) (clone $doomed)->min('seq');
                $until = (int) (clone $doomed)->max('seq');
                $stored = (clone $doomed)->count();

                // The oldest stretch is whole, or deleting it would hide what is already missing.
                if ($stored > 0 && $stored !== $until - $from + 1) {
                    throw AuditFailed::prunedOverGap($from, $until, $stored);
                }

                $deleted = $doomed->delete();

                // What is left starts right after what was deleted.
                if ($deleted !== $stored || $deleted > 0 && ($this->chain->startsAfter()['seq'] ?? null) !== $until) {
                    throw AuditFailed::prunedOverGap($from, $until, $deleted);
                }
            }

            $this->auditor->record(
                new AuditLogPruned($payload->legalHold, $payload->retention, $deleted, $this->chain->startsAfter()),
                new SystemActor('larasell:prune-audit-log'),
            );

            return $deleted;
        });

        // After the log, so a failing export can't keep the log from being pruned.
        return new PruneAuditLogResult($deleted, $this->cleanUpExports());
    }

    /**
     * Deletes expired exports, whatever the retention period, since they are copies, not the log,
     * and fails exports that never finished, e.g. because their worker died.
     *
     * @return array<int, string> Why each export that wasn't deleted, by id.
     */
    private function cleanUpExports(): array
    {
        $undeleted = [];

        AuditLogExport::query()->where('expires_at', '<=', Date::now()->utc())->each(function (AuditLogExport $export) use (&$undeleted): void {
            try {
                DeleteExpiredAuditLogExport::delete($export);
            } catch (Throwable $exception) {
                $undeleted[$export->id] = $exception->getMessage();
            }
        });

        AuditLogExport::query()
            ->where('status', AuditLogExportStatus::Pending)
            ->where('created_at', '<=', Date::now()->utc()->subHour())
            ->each(fn (AuditLogExport $export) => ExportAuditLog::markFailed($export, "The export didn't finish within an hour."));

        return $undeleted;
    }
}
