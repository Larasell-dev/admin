<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\ProfilePageData;
use Larasell\Admin\Data\ViewModel\AdminData;
use Larasell\Admin\Http\Requests\ProfileRequest;
use Larasell\Admin\Models\Admin;

class ProfileController
{
    public function edit(#[CurrentUser('larasell-admin')] Admin $admin): Response
    {
        return Inertia::render('Profile', new ProfilePageData(
            admin: AdminData::fromModel($admin),
            updateUrl: route('larasell.admin.profile.update'),
            avatarUploadUrl: route('larasell.admin.profile.avatar.store'),
        ));
    }

    public function update(ProfileRequest $request, #[CurrentUser('larasell-admin')] Admin $admin): RedirectResponse
    {
        $attributes = $request->safe()->only(['name', 'email']);
        $disk = Storage::disk(config('larasell.admin.panel.disk'));
        $previousAvatar = $admin->avatar_path;

        if ($request->filled('avatar')) {
            $attributes['avatar_path'] = $request->validated('avatar');
            $request->session()->forget(AvatarController::PENDING_SESSION_KEY);
        } elseif ($request->boolean('remove_avatar')) {
            $attributes['avatar_path'] = null;
        }

        $admin->update($attributes);

        if ($previousAvatar !== null && $previousAvatar !== $admin->avatar_path) {
            $disk->delete($previousAvatar);
        }

        return redirect()->route('larasell.admin.profile');
    }
}
