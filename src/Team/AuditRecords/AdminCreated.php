<?php

namespace Larasell\Admin\Team\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditCategory;
use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Admin\Platform\Auth\Models\Admin;

/**
 * An admin was added. Never with their password.
 *
 * @internal Recorded by CreateAdmin. Its event name, admin.created, is stable.
 */
class AdminCreated extends AuditRecord
{
    public function __construct(private readonly Admin $admin) {}

    public function event(): string
    {
        return 'admin.created';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::Security;
    }

    public function subject(): Subject
    {
        return new Subject('admin', $this->admin->getKey());
    }

    public function properties(): array
    {
        return ['email' => $this->admin->email];
    }
}
