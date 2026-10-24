<?php

namespace Larasell\Admin\Actions;

use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Exceptions\AdminRemovalFailed;
use Larasell\Admin\Models\Admin;

class DeleteAdmin
{
    /**
     * Deletes the admin and their avatar, unless they are the last admin.
     *
     * @throws AdminRemovalFailed
     */
    public function handle(Admin $admin): void
    {
        if (Admin::query()->count() === 1) {
            throw AdminRemovalFailed::lastAdmin($admin->email);
        }

        if ($admin->delete() === false) {
            throw AdminRemovalFailed::notDeleted($admin->email);
        }

        if (Admin::query()->whereKey($admin->getKey())->exists()) {
            throw AdminRemovalFailed::stillExists($admin->email);
        }

        if ($admin->avatar_path !== null && ! Storage::disk(config('larasell-admin.disk'))->delete($admin->avatar_path)) {
            throw AdminRemovalFailed::avatarNotDeleted($admin->email, $admin->avatar_path);
        }
    }
}
