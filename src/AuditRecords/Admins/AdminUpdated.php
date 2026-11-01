<?php

namespace Larasell\Admin\AuditRecords\Admins;

use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;
use Larasell\Admin\Models\Admin;

/**
 * An admin's name, email or password changed. Which of them, and the email before and after,
 * since it signs them in; never the password.
 *
 * @internal Recorded by UpdateAdmin. Its event name, admin.updated, is stable.
 */
class AdminUpdated extends AuditRecord
{
    /**
     * @var list<string>
     */
    public readonly array $changed;

    private readonly string $emailFrom;

    /**
     * Takes the admin before it is saved, while it still knows what changed.
     */
    public function __construct(private readonly Admin $admin)
    {
        $this->changed = array_values(array_filter(['name', 'email', 'password'], fn (string $field): bool => $admin->isDirty($field)));
        $this->emailFrom = $admin->getOriginal('email');
    }

    public function event(): string
    {
        return 'admin.updated';
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
        return [
            'changed' => $this->changed,
            ...in_array('email', $this->changed, true) ? ['email' => ['from' => $this->emailFrom, 'to' => $this->admin->email]] : [],
        ];
    }
}
