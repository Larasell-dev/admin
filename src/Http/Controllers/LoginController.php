<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\Audit\UnknownActor;
use Larasell\Admin\AuditRecords\Admins\AdminSignedIn;
use Larasell\Admin\AuditRecords\Admins\AdminSignInFailed;
use Larasell\Admin\Data\View\LoginPageData;
use Larasell\Admin\Http\Requests\LoginRequest;
use Larasell\Admin\Models\Admin;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class LoginController
{
    public function create(): Response
    {
        return Inertia::render('Login', new LoginPageData(
            loginUrl: route('larasell.admin.login.store'),
            devLoginUrl: app()->environment('local') ? route('larasell.admin.dev.login') : null,
        ));
    }

    public function store(LoginRequest $request, Auditor $auditor): RedirectResponse|SymfonyResponse
    {
        $guard = Auth::guard('larasell-admin');
        $credentials = $request->safe()->only(['email', 'password']);

        if (! $guard->attempt($credentials)) {
            $tried = Admin::query()->where('email', $credentials['email'])->first();
            $auditor->record(new AdminSignInFailed($tried, $credentials['email'], $request->ip()), new UnknownActor('No one was signed in: the sign-in failed.'));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        /** @var Admin $admin */
        $admin = $guard->user();

        try {
            $auditor->record(new AdminSignedIn($admin), $admin);
        } catch (Throwable $e) {
            // No session without its record.
            $guard->logout();

            throw $e;
        }

        $request->session()->regenerate();

        // Signing back in from the session expired dialog, which stays on its page.
        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return redirect()->intended(route('larasell.admin.home'));
    }
}
