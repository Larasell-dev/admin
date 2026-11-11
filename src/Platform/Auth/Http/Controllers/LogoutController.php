<?php

namespace Larasell\Admin\Platform\Auth\Http\Controllers;

use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Larasell\Admin\Platform\Auth\AuditRecords\AdminSignedOut;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Audit\Auditor;
use Larasell\Larasell\Audit\Origin;

class LogoutController
{
    public function __invoke(Request $request, Auditor $auditor, #[CurrentUser('larasell-admin')] Admin $admin): RedirectResponse
    {
        // Before signing out, so a failure to record it leaves them signed in to try again.
        $auditor->record(new AdminSignedOut($admin), Origin::web($admin));

        Auth::guard('larasell-admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('larasell.admin.login');
    }
}
