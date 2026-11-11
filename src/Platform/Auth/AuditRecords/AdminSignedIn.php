<?php

namespace Larasell\Admin\Platform\Auth\AuditRecords;

use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Audit\AuditRecord;
use Larasell\Larasell\Audit\Enums\AuditCategory;
use Larasell\Larasell\Audit\Subject;

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
