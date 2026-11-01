<?php

namespace Larasell\Admin\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\Audit\SystemActor;
use Larasell\Admin\AuditRecords\AuditLog\AuditLogPruned;

/**
 * Deletes audit records older than their category's retention period, set in
 * config/larasell/admin/audit.php, and records that it ran.
 */
class PruneAuditLogCommand extends Command
{
    protected $signature = 'larasell:prune-audit-log';

    protected $description = 'Delete audit records older than their retention period.';

    public function handle(Auditor $auditor): int
    {
        $retentionDays = $this->retentionDays();

        if ($retentionDays === null) {
            return self::FAILURE;
        }

        $legalHold = (bool) config('larasell.admin.audit.legal_hold');
        $now = Date::now()->utc();

        $deleted = DB::transaction(function () use ($auditor, $retentionDays, $legalHold, $now): array {
            $deleted = [];

            foreach ($retentionDays as $category => $days) {
                $deleted[$category] = $legalHold || $days === null ? 0 : DB::table('larasell_audit_log')
                    ->where('category', $category)
                    ->where('occurred_at', '<', $now->copy()->subDays($days)->format('Y-m-d H:i:s.u'))
                    ->delete();
            }

            $auditor->record(new AuditLogPruned($legalHold, $retentionDays, $deleted), new SystemActor('larasell:prune-audit-log'));

            return $deleted;
        });

        if ($legalHold) {
            $this->components->warn('No audit records were deleted, since they are under a legal hold.');

            return self::SUCCESS;
        }

        if (array_filter($retentionDays) === []) {
            $this->components->info('No retention period is set in config/larasell/admin/audit.php, so every audit record was kept.');

            return self::SUCCESS;
        }

        $this->components->info(array_sum($deleted).' audit records deleted.');

        return self::SUCCESS;
    }

    /**
     * Each category's retention period in days, or null after reporting why the settings are invalid.
     *
     * @return ?array<string, ?int>
     */
    private function retentionDays(): ?array
    {
        $categories = array_map(fn (AuditCategory $category): string => $category->value, AuditCategory::cases());
        /** @var array<array-key, mixed> $overrides */
        $overrides = config('larasell.admin.audit.retention.categories') ?? [];
        $unknown = array_diff(array_keys($overrides), $categories);

        if ($unknown !== []) {
            $this->components->error('Unknown audit categories in larasell.admin.audit.retention.categories: '.implode(', ', $unknown).'. Use '.implode(', ', $categories).'.');

            return null;
        }

        $retentionDays = [];

        foreach ($categories as $category) {
            [$key, $days] = array_key_exists($category, $overrides)
                ? ["categories.{$category}", $overrides[$category]]
                : ['default_days', config('larasell.admin.audit.retention.default_days')];

            if ($days === null || $days === '') {
                $retentionDays[$category] = null;
            } elseif (is_int($days) && $days > 0 || is_string($days) && preg_match('/^[1-9][0-9]*$/', $days) === 1) {
                $retentionDays[$category] = (int) $days;
            } else {
                $this->components->error("larasell.admin.audit.retention.{$key} must be a whole number of days above 0, or null to keep records forever.");

                return null;
            }
        }

        return $retentionDays;
    }
}
