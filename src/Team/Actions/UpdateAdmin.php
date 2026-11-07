<?php

namespace Larasell\Admin\Team\Actions;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Audit\Actors\Actor;
use Larasell\Admin\Platform\Audit\Actors\UnknownActor;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Team\AuditRecords\AdminUpdated;

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
    public function handle(Admin $admin, array $attributes, Actor|UnknownActor $actor): void
    {
        $admin->fill($attributes);
        $record = new AdminUpdated($admin);

        DB::transaction(function () use ($admin, $record, $actor): void {
            $admin->save();

            if ($record->changed !== []) {
                $this->auditor->record($record, $actor);
            }
        });
    }
}
