<?php

namespace Larasell\Admin\Platform\Audit\Facades;

use Illuminate\Support\Facades\Facade;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Chain\AuditChain;
use Larasell\Admin\Platform\Audit\Testing\AuditFake;

/**
 * Records audit records, like an injected Auditor: `Audit::record(new ApiTokenCreated($apiToken))`.
 *
 * In tests, Audit::fake() keeps records in memory, to assert like Event::fake():
 * `Audit::assertRecorded(AdminCreated::class, fn ($record, $actor, $source) => ...)`.
 *
 * @method static void record(\Larasell\Admin\Platform\Audit\AuditRecord $record, \Larasell\Admin\Platform\Audit\Actors\Actor|\Larasell\Admin\Platform\Audit\Actors\UnknownActor|null $actor = null)
 * @method static void recordForEvent(\Larasell\Admin\Platform\Audit\AuditRecord $record, string $unknownReason)
 * @method static void assertRecorded(class-string<AuditRecord> $record, (callable(AuditRecord, \Larasell\Admin\Platform\Audit\Actors\Actor|\Larasell\Admin\Platform\Audit\Actors\UnknownActor, \Larasell\Admin\Platform\Audit\Enums\AuditSource): bool)|null $callback = null)
 * @method static void assertRecordedTimes(class-string<AuditRecord> $record, int $times = 1)
 * @method static void assertNotRecorded(class-string<AuditRecord> $record, (callable(AuditRecord, \Larasell\Admin\Platform\Audit\Actors\Actor|\Larasell\Admin\Platform\Audit\Actors\UnknownActor, \Larasell\Admin\Platform\Audit\Enums\AuditSource): bool)|null $callback = null)
 * @method static void assertNothingRecorded()
 *
 * @see Auditor
 * @see AuditFake
 *
 * @public
 */
class Audit extends Facade
{
    /**
     * Keeps the given records, or all of them, in memory instead of the audit log. The auditor's
     * checks still run, and the actor and source are resolved as they would be.
     *
     * @param  list<class-string<AuditRecord>>  $recordsToFake
     */
    public static function fake(array $recordsToFake = []): AuditFake
    {
        $root = static::getFacadeRoot();
        $real = $root instanceof AuditFake ? $root->auditor : $root;

        return tap(new AuditFake($real, app(AuditChain::class), $recordsToFake), static::swap(...));
    }

    protected static function getFacadeAccessor(): string
    {
        return Auditor::class;
    }
}
