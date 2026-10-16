<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Http\Requests\ProfileRequest;
use Larasell\Admin\Models\Admin;

class ProfileController
{
    public function edit(#[CurrentUser('larasell-admin')] Admin $admin): Response
    {
        return Inertia::render('Profile', [
            'admin' => [
                'name' => $admin->name,
                'email' => $admin->email,
            ],
            'updateUrl' => route('larasell.admin.profile.update'),
        ]);
    }

    public function update(ProfileRequest $request, #[CurrentUser('larasell-admin')] Admin $admin): RedirectResponse
    {
        $admin->update($request->safe()->only(['name', 'email']));

        return redirect()->route('larasell.admin.profile');
    }
}
