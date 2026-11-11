<?php

namespace Larasell\Admin\Team\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Team\AuditRecords\AdminDeleted;
use Larasell\Admin\Team\Exceptions\AdminRemovalFailed;
use Larasell\Larasell\Audit\Auditor;
use Larasell\Larasell\Audit\Origin;

class DeleteAdmin
{
    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Deletes the admin and their avatar, unless they are the last admin. Audited as deleted by
     * the origin: the signed-in admin, or a SystemActor in jobs and commands. The admin and their
     * avatar stay if that can't be audited.
     *
     * @throws AdminRemovalFailed
     */
    public function handle(Admin $admin, Origin $origin): void
    {
        if (Admin::query()->count() === 1) {
            throw AdminRemovalFailed::lastAdmin($admin->email);
        }

        // Read before anything is deleted: a stored path outside avatars/ throws here.
        $avatar = $admin->avatar_path;

        DB::transaction(function () use ($admin, $origin): void {
            if ($admin->delete() === false) {
                throw AdminRemovalFailed::notDeleted($admin->email);
            }

            if (Admin::query()->whereKey($admin->getKey())->exists()) {
                throw AdminRemovalFailed::stillExists($admin->email);
            }

            $this->auditor->record(new AdminDeleted($admin), $origin);
        });

        // Only once the deletion is committed: a file can't be rolled back.

        if ($avatar !== null && ! Storage::disk(config('larasell.admin.panel.disk'))->delete($avatar->path)) {
            throw AdminRemovalFailed::avatarNotDeleted($admin->email, $avatar->path);
        }
    }
}
