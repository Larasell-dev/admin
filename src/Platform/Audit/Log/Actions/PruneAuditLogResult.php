<?php

namespace Larasell\Admin\Platform\Audit\Log\Actions;

/**
 * What pruning did: how many records it deleted, and the exports it couldn't delete, with why.
 */
final readonly class PruneAuditLogResult
{
    /**
     * @param  array<int, string>  $undeletedExports  Why each export, by id, wasn't deleted.
     */
    public function __construct(public int $deleted, public array $undeletedExports) {}
}
