<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\AuditRecords\Admins\AdminSignedOut;
use Larasell\Admin\Models\Admin;

class LogoutController
{
    public function __invoke(Request $request, Auditor $auditor, #[CurrentUser('larasell-admin')] Admin $admin): RedirectResponse
    {
        // Before signing out, so a failure to record it leaves them signed in to try again.
        $auditor->record(new AdminSignedOut($admin), $admin);

        Auth::guard('larasell-admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('larasell.admin.login');
    }
}
