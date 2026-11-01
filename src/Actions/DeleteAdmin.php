<?php

namespace Larasell\Admin\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Audit\Actor;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\Audit\UnknownActor;
use Larasell\Admin\AuditRecords\Admins\AdminDeleted;
use Larasell\Admin\Exceptions\AdminRemovalFailed;
use Larasell\Admin\Models\Admin;

class DeleteAdmin
{
    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Deletes the admin and their avatar, unless they are the last admin. Audited as deleted by
     * the actor: the signed-in admin, or a SystemActor in jobs and commands. The admin and their
     * avatar stay if that can't be audited.
     *
     * @throws AdminRemovalFailed
     */
    public function handle(Admin $admin, Actor|UnknownActor $actor): void
    {
        if (Admin::query()->count() === 1) {
            throw AdminRemovalFailed::lastAdmin($admin->email);
        }

        DB::transaction(function () use ($admin, $actor): void {
            if ($admin->delete() === false) {
                throw AdminRemovalFailed::notDeleted($admin->email);
            }

            if (Admin::query()->whereKey($admin->getKey())->exists()) {
                throw AdminRemovalFailed::stillExists($admin->email);
            }

            $this->auditor->record(new AdminDeleted($admin), $actor);
        });

        // Only once the deletion is committed: a file can't be rolled back.

        if ($admin->avatar_path !== null && ! Storage::disk(config('larasell.admin.panel.disk'))->delete($admin->avatar_path)) {
            throw AdminRemovalFailed::avatarNotDeleted($admin->email, $admin->avatar_path);
        }
    }
}
