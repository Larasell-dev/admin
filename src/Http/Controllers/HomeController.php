<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\HomePageData;

class HomeController
{
    public function __invoke(): Response
    {
        return Inertia::render('Home', new HomePageData(
            createProductUrl: Route::has('larasell.admin.products.create') ? route('larasell.admin.products.create') : null,
            createCategoryUrl: route('larasell.admin.categories.create'),
            inviteAdminUrl: route('larasell.admin.settings.admins.create'),
            helpEmail: 'nils@larasell.dev',
        ));
    }
}
