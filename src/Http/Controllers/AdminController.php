<?php

namespace Larasell\Admin\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\AdminsPageData;
use Larasell\Admin\Tables\Panel\Admins;

class AdminController
{
    public function index(): Response
    {
        return Inertia::render('Admin/Index', new AdminsPageData(
            admins: Admins::make(),
        ));
    }
}
