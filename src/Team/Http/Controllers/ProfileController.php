<?php

namespace Larasell\Admin\Team\Http\Controllers;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Team\Actions\UpdateAdmin;
use Larasell\Admin\Team\Data\AdminData;
use Larasell\Admin\Team\Data\ProfilePageData;
use Larasell\Admin\Team\Http\Requests\ProfileRequest;
use Larasell\Larasell\Audit\Origin;

class ProfileController
{
    public function edit(#[CurrentUser('larasell-admin')] Admin $admin): Response
    {
        return Inertia::render('Team/Pages/Profile', new ProfilePageData(
            admin: AdminData::fromModel($admin),
            updateUrl: route('larasell.admin.profile.update'),
            avatarUploadUrl: route('larasell.admin.profile.avatar.store'),
        ));
    }

    public function update(ProfileRequest $request, UpdateAdmin $updateAdmin, #[CurrentUser('larasell-admin')] Admin $admin): RedirectResponse
    {
        $attributes = $request->safe()->only(['name', 'email']);
        $disk = Storage::disk(config('larasell.admin.panel.disk'));
        // Read before anything is saved: a stored path outside avatars/ throws here.
        $previousAvatar = $admin->avatar_path;

        if ($request->filled('avatar')) {
            $attributes['avatar_path'] = $request->validated('avatar');
            $request->session()->forget(AvatarController::PENDING_SESSION_KEY);
        } elseif ($request->boolean('remove_avatar')) {
            $attributes['avatar_path'] = null;
        }

        $updateAdmin->handle($admin, $attributes, Origin::web($admin));

        if ($previousAvatar !== null && $previousAvatar->path !== $admin->avatar_path?->path) {
            $disk->delete($previousAvatar->path);
        }

        return redirect()->route('larasell.admin.profile');
    }
}
