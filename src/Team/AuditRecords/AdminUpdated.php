<?php

namespace Larasell\Admin\Team\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Admin\Platform\Auth\Models\Admin;

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
     * Takes the admin just after it was saved, so it describes what changed in the database
     * rather than what was asked for.
     */
    public function __construct(private readonly Admin $admin, string $emailFrom)
    {
        $this->changed = array_values(array_filter(['name', 'email', 'password'], fn (string $field): bool => $admin->wasChanged($field)));
        $this->emailFrom = $emailFrom;
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
