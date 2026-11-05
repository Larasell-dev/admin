<?php

namespace Larasell\Admin\Platform\Panel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Larasell\Chronicle\Facades\Chronicle;
use Larasell\Larasell\Facades\Entitlement;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the installation's licensing plan on the wide event of admin requests. The plan decides
 * every entitlement, so it explains why a Pro feature was hidden or refused.
 */
class LogLicensingPlan
{
    public function handle(Request $request, Closure $next): Response
    {
        Chronicle::add('licensing.plan', Entitlement::plan()->value);

        return $next($request);
    }
}
