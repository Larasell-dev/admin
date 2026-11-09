<?php

namespace Larasell\Admin\Team\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Platform\Audit\Actors\Actor;
use Larasell\Admin\Platform\Audit\Actors\UnknownActor;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\Support\Assert;
use Larasell\Admin\Team\AuditRecords\AdminDeleted;
use Larasell\Admin\Team\Exceptions\AdminRemovalFailed;
use Larasell\Admin\Team\Exceptions\InvalidAvatarPath;
use Larasell\Admin\Team\Http\Controllers\AvatarController;

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

        // In production an avatar path outside avatars/ is reported and its file kept.
        if ($admin->avatar_path === null || ! Assert::that(AvatarController::isAvatar($admin->avatar_path), fn () => InvalidAvatarPath::outsideAvatars($admin->avatar_path))) {
            return;
        }

        if (! Storage::disk(config('larasell.admin.panel.disk'))->delete($admin->avatar_path)) {
            throw AdminRemovalFailed::avatarNotDeleted($admin->email, $admin->avatar_path);
        }
    }
}
