<?php

namespace Larasell\Admin\Audit;

use Larasell\Admin\Exceptions\AuditFailed;

/**
 * A job or command acting on its own, named for what it does, e.g. inventory-reconciliation.
 *
 * @public
 */
final readonly class SystemActor implements Actor
{
    public function __construct(public string $name)
    {
        if (trim($name) === '') {
            throw AuditFailed::unnamedActor(self::class, 'name');
        }
    }

    public function auditActorType(): string
    {
        return 'system';
    }

    public function auditActorId(): string
    {
        return $this->name;
    }
}
