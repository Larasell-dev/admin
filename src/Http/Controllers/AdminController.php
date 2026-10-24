<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Actions\CreateAdmin;
use Larasell\Admin\Data\View\AdminsPageData;
use Larasell\Admin\Data\View\CreateAdminPageData;
use Larasell\Admin\Tables\Panel\Admins;

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

    public function store(Request $request, CreateAdmin $createAdmin): RedirectResponse
    {
        $createAdmin->handle($request->only(['name', 'email', 'password']));

        return redirect()->route('larasell.admin.settings.admins.index');
    }
}
