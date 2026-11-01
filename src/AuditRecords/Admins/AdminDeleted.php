<?php

namespace Larasell\Admin\AuditRecords\Admins;

use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;
use Larasell\Admin\Models\Admin;

/**
 * An admin was deleted. Keeps their email, since their row is gone.
 *
 * @internal Recorded by DeleteAdmin. Its event name, admin.deleted, is stable.
 */
class AdminDeleted extends AuditRecord
{
    public function __construct(private readonly Admin $admin) {}

    public function event(): string
    {
        return 'admin.deleted';
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
