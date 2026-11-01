<?php

namespace Larasell\Admin\AuditRecords\Admins;

use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;
use Larasell\Admin\Models\Admin;

/**
 * An admin signed in to the admin panel. Changes nothing, so it needs no transaction.
 *
 * @internal Recorded by LoginController. Its event name, admin.signed_in, is stable.
 */
class AdminSignedIn extends AuditRecord
{
    public function __construct(private readonly Admin $admin) {}

    public function event(): string
    {
        return 'admin.signed_in';
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
        return [];
    }

    public function requiresTransaction(): bool
    {
        return false;
    }
}
