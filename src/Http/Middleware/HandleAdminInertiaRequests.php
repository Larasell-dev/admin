<?php

namespace Larasell\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Larasell\Admin\AdminPanel;
use Symfony\Component\HttpFoundation\Response;

class HandleAdminInertiaRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $panel = app(AdminPanel::class);

        config([
            'inertia.ssr.enabled' => false,
            'inertia.pages.paths' => [
                ...config('inertia.pages.paths', []),
                realpath(__DIR__.'/../../../resources/js/Pages'),
                ...$panel->pagePaths(),
            ],
        ]);

        Inertia::share('navigation', fn (): array => $panel->navigationItems());
        Inertia::setRootView('larasell-admin::app');

        return $next($request);
    }
}
