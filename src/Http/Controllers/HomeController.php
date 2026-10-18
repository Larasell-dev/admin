<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\HomePageData;
use Larasell\Admin\Data\ViewModel\AdminData;
use Larasell\Admin\Models\Admin;

class HomeController
{
    public function __invoke(#[CurrentUser('larasell-admin')] Admin $admin): Response
    {
        return Inertia::render('Home', new HomePageData(
            admin: AdminData::fromModel($admin),
        ));
    }
}
