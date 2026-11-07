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
use Larasell\Admin\Team\Data\CreateMemberPageData;
use Larasell\Admin\Team\Data\EditMemberPageData;
use Larasell\Admin\Team\Data\TeamPageData;
use Larasell\Admin\Team\Http\Requests\MemberRequest;
use Larasell\Admin\Team\Tables\Members;
use Laravel\Head\Facades\Head;

class TeamController
{
    public function index(): Response
    {
        return Inertia::render('Team/Pages/Index', new TeamPageData(
            createUrl: route('larasell.admin.settings.team.create'),
            members: Members::make(),
        ));
    }

    public function create(): Response
    {
        return Inertia::render('Team/Pages/Create', new CreateMemberPageData(
            storeUrl: route('larasell.admin.settings.team.store'),
            indexUrl: route('larasell.admin.settings.team.index'),
        ));
    }

    public function store(Request $request, CreateAdmin $createAdmin, #[CurrentUser('larasell-admin')] Admin $signedIn): RedirectResponse
    {
        $admin = $createAdmin->handle($request->only(['name', 'email', 'password']), $signedIn);

        return redirect()->route('larasell.admin.settings.team.edit', $admin);
    }

    public function edit(Admin $admin): Response
    {
        Head::title($admin->name);

        return Inertia::render('Team/Pages/Edit', new EditMemberPageData(
            member: AdminData::fromModel($admin),
            updateUrl: route('larasell.admin.settings.team.update', $admin),
            indexUrl: route('larasell.admin.settings.team.index'),
        ));
    }

    public function update(MemberRequest $request, Admin $admin, UpdateAdmin $updateAdmin, #[CurrentUser('larasell-admin')] Admin $signedIn): RedirectResponse
    {
        $updateAdmin->handle($admin, array_filter($request->validated(), fn (mixed $value): bool => $value !== null), $signedIn);

        return redirect()->route('larasell.admin.settings.team.edit', $admin);
    }
}
