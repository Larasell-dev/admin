<?php

namespace Larasell\Admin\Http\Controllers\Dev;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Larasell\Admin\Models\Admin;

/**
 * Creates and destroys admin sessions without credentials. Only registered in
 * the local environment. Both actions are GET so they work from a browser bar.
 */
class SessionController
{
    public function store(Request $request): RedirectResponse
    {
        $email = $request->query('email');

        $admin = is_string($email)
            ? Admin::where('email', $email)->firstOrFail()
            : Admin::orderBy('id')->first() ?? Admin::create([
                'name' => 'Dev Admin',
                'email' => 'dev@example.com',
                'password' => Str::random(32),
            ]);

        Auth::guard('larasell-admin')->login($admin);
        $request->session()->regenerate();

        return redirect($this->redirectTarget($request) ?? route('larasell.admin.home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('larasell-admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($this->redirectTarget($request) ?? route('larasell.admin.login'));
    }

    /**
     * Only same-site paths are accepted, so the redirect can't leave the app.
     */
    private function redirectTarget(Request $request): ?string
    {
        $redirect = $request->query('redirect');

        return is_string($redirect) && str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//')
            ? $redirect
            : null;
    }
}
