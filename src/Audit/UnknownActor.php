<?php

namespace Larasell\Admin\Audit;

use Larasell\Admin\Exceptions\AuditFailed;

/**
 * For the rare action whose actor can't be known, with the reason why, e.g. data imported from
 * a previous system. Never a stand-in for an actor that could be passed.
 *
 * @public
 */
final readonly class UnknownActor
{
    public function __construct(public string $reason)
    {
        if (trim($reason) === '') {
            throw AuditFailed::unnamedActor(self::class, 'reason');
        }
    }
}
