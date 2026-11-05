<?php

namespace Larasell\Admin\Platform\Auth\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditCategory;
use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Admin\Platform\Auth\Models\Admin;

/**
 * Someone failed to sign in, against the admin whose email they tried, if any. Keeps the email
 * only when no admin has it, since the subject already names the admin, and never the password.
 *
 * @internal Recorded by LoginController. Its event name, admin.sign_in_failed, is stable.
 */
class AdminSignInFailed extends AuditRecord
{
    public function __construct(private readonly ?Admin $admin, private readonly string $email, private readonly ?string $ip) {}

    public function event(): string
    {
        return 'admin.sign_in_failed';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::Security;
    }

    public function subject(): ?Subject
    {
        return $this->admin === null ? null : new Subject('admin', $this->admin->getKey());
    }

    public function properties(): array
    {
        return [
            ...$this->admin === null ? ['email' => $this->email] : [],
            'ip' => $this->ip,
        ];
    }

    public function requiresTransaction(): bool
    {
        return false;
    }
}
