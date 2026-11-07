<?php

namespace Larasell\Admin\Platform\Panel\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Platform\Panel\Data\HomePageData;

class HomeController
{
    public function __invoke(): Response
    {
        return Inertia::render('Platform/Panel/Pages/Home', new HomePageData(
            createProductUrl: Route::has('larasell.admin.products.create') ? route('larasell.admin.products.create') : null,
            createCategoryUrl: Route::has('larasell.admin.categories.create') ? route('larasell.admin.categories.create') : null,
            inviteMemberUrl: route('larasell.admin.settings.team.create'),
            helpEmail: 'nils@larasell.dev',
        ));
    }
}
