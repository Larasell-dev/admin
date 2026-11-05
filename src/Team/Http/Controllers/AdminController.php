<?php

namespace Larasell\Admin\Team\Http\Controllers;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Team\Actions\CreateAdmin;
use Larasell\Admin\Team\Actions\UpdateAdmin;
use Larasell\Admin\Team\Data\AdminData;
use Larasell\Admin\Team\Data\AdminsPageData;
use Larasell\Admin\Team\Data\CreateAdminPageData;
use Larasell\Admin\Team\Data\EditAdminPageData;
use Larasell\Admin\Team\Http\Requests\AdminRequest;
use Larasell\Admin\Team\Tables\Admins;
use Laravel\Head\Facades\Head;

class AdminController
{
    public function index(): Response
    {
        return Inertia::render('Admin/Index', new AdminsPageData(
            createUrl: route('larasell.admin.settings.admins.create'),
            admins: Admins::make(),
        ));
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Create', new CreateAdminPageData(
            storeUrl: route('larasell.admin.settings.admins.store'),
            indexUrl: route('larasell.admin.settings.admins.index'),
        ));
    }

    public function store(Request $request, CreateAdmin $createAdmin, #[CurrentUser('larasell-admin')] Admin $signedIn): RedirectResponse
    {
        $admin = $createAdmin->handle($request->only(['name', 'email', 'password']), $signedIn);

        return redirect()->route('larasell.admin.settings.admins.edit', $admin);
    }

    public function edit(Admin $admin): Response
    {
        Head::title($admin->name);

        return Inertia::render('Admin/Edit', new EditAdminPageData(
            editedAdmin: AdminData::fromModel($admin),
            updateUrl: route('larasell.admin.settings.admins.update', $admin),
            indexUrl: route('larasell.admin.settings.admins.index'),
        ));
    }

    public function update(AdminRequest $request, Admin $admin, UpdateAdmin $updateAdmin, #[CurrentUser('larasell-admin')] Admin $signedIn): RedirectResponse
    {
        $updateAdmin->handle($admin, array_filter($request->validated(), fn (mixed $value): bool => $value !== null), $signedIn);

        return redirect()->route('larasell.admin.settings.admins.edit', $admin);
    }
}
