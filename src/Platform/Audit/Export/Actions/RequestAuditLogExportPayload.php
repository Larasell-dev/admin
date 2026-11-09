<?php

namespace Larasell\Admin\Platform\Audit\Export\Actions;

/**
 * An admin asking for an export of the whole audit log.
 */
final readonly class RequestAuditLogExportPayload
{
    public function __construct(public int $adminId) {}
}
