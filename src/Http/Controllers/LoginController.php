<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\LoginPageData;
use Larasell\Admin\Http\Requests\LoginRequest;

class LoginController
{
    public function create(): Response
    {
        return Inertia::render('Login', new LoginPageData(
            loginUrl: route('larasell.admin.login.store'),
        ));
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::guard('larasell-admin')->attempt($request->safe()->only(['email', 'password']))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('larasell.admin.home'));
    }
}
