<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController
{
    public function __invoke(Request $request): Response
    {
        $admin = $request->user('larasell-admin') ?? abort(401);

        return Inertia::render('Home', [
            'admin' => [
                'name' => $admin->name,
                'email' => $admin->email,
            ],
            'logoutUrl' => route('larasell.admin.logout'),
        ]);
    }
}
