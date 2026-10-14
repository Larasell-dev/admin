<?php

namespace Larasell\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('larasell-admin')->check()) {
            return redirect()->guest(route('larasell.admin.login'));
        }

        return $next($request);
    }
}
