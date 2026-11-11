<?php

namespace Larasell\Admin\Team\Actions;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Team\AuditRecords\AdminUpdated;
use Larasell\Larasell\Audit\Auditor;
use Larasell\Larasell\Audit\Origin;

/**
 * @internal Admins are changed in the settings and on their profile page.
 */
class UpdateAdmin
{
    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Saves the attributes, audited when the admin's name, email or password changes. Nothing
     * changes if that can't be audited.
     *
     * @param  array<string, mixed>  $attributes  Validated by the caller.
     */
    public function handle(Admin $admin, array $attributes, Origin $origin): void
    {
        $emailFrom = $admin->email;
        $admin->fill($attributes);

        DB::transaction(function () use ($admin, $emailFrom, $origin): void {
            $admin->save();
            $record = new AdminUpdated($admin, $emailFrom);

            if ($record->changed !== []) {
                $this->auditor->record($record, $origin);
            }
        });
    }
}
