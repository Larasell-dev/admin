<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard(config('larasell-admin.guard'))->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('larasell.admin.login');
    }
}
