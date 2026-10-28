<?php

namespace Larasell\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('larasell-admin')->check()) {
            // The session expired dialog, after the admin signed back in from another tab.
            return $request->expectsJson()
                ? response()->noContent()
                : redirect()->route('larasell.admin.home');
        }

        return $next($request);
    }
}
