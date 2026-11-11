<?php

namespace Workbench\App\AuditLog;

use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Larasell\Larasell\Audit\Actors\SystemActor;
use Larasell\Larasell\Audit\Auditor;
use Larasell\Larasell\Audit\AuditRecord;
use Larasell\Larasell\Audit\Enums\AuditCategory;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Audit\Subject;

/**
 * Fills the playground's audit log with fake records, e.g. to try exports, pruning and
 * verification on a large log. Records go through the Auditor, so they join the chain and the
 * log still verifies. Spread over the past two years, oldest first.
 */
class FakeAuditLogCommand extends Command
{
    protected $signature = 'workbench:fake-audit-log {count=100000 : How many records to add}';

    protected $description = 'Add fake audit records to the playground, for trying out large logs.';

    private const array EVENTS = [
        ['product.updated', AuditCategory::BusinessCritical, 'product'],
        ['product.deleted', AuditCategory::BusinessCritical, 'product'],
        ['sales_channel.updated', AuditCategory::BusinessCritical, 'sales_channel'],
        ['seo.old_url_removed', AuditCategory::BusinessCritical, 'seo_url'],
        ['admin.signed_in', AuditCategory::Security, 'admin'],
        ['admin.updated', AuditCategory::Security, 'admin'],
        ['api_token.created', AuditCategory::Security, 'api_token'],
    ];

    public function handle(Auditor $auditor): int
    {
        $count = (int) $this->argument('count');
        $start = Date::now()->subYears(2);
        $step = (int) max(1, intdiv(2 * 365 * 24 * 3600, max(1, $count)));
        $bar = $this->output->createProgressBar($count);

        // One transaction per batch: far faster than one per record, and each still takes the chain's lock.
        for ($done = 0; $done < $count; $done += 1000) {
            DB::transaction(function () use ($auditor, $done, $count, $start, $step): void {
                for ($i = $done; $i < min($done + 1000, $count); $i++) {
                    [$event, $category, $subject] = self::EVENTS[$i % count(self::EVENTS)];
                    $auditor->record($this->record($event, $category, new Subject($subject, random_int(1, 500)), $start->copy()->addSeconds($i * $step)), Origin::cli(new SystemActor('workbench:fake-audit-log')));
                }
            });
            $bar->advance(min(1000, $count - $done));
        }

        $bar->finish();
        $this->newLine();
        $this->components->info("{$count} fake audit records added.");

        return self::SUCCESS;
    }

    private function record(string $event, AuditCategory $category, Subject $subject, CarbonInterface $at): AuditRecord
    {
        return new class($event, $category, $subject, $at) extends AuditRecord
        {
            public function __construct(private readonly string $name, private readonly AuditCategory $kind, private readonly Subject $about, private readonly CarbonInterface $at) {}

            public function event(): string
            {
                return $this->name;
            }

            public function category(): AuditCategory
            {
                return $this->kind;
            }

            public function subject(): Subject
            {
                return $this->about;
            }

            public function properties(): array
            {
                return ['fake' => true, 'changed' => ['name', 'status']];
            }

            public function occurredAt(): CarbonInterface
            {
                return $this->at;
            }
        };
    }
}
