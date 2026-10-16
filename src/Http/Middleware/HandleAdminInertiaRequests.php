<?php

namespace Larasell\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Models\Admin;
use Symfony\Component\HttpFoundation\Response;

class HandleAdminInertiaRequests extends Middleware
{
    protected $rootView = 'larasell-admin::app';

    public function handle(Request $request, Closure $next): Response
    {
        config([
            'inertia.ssr.enabled' => false,
            'inertia.pages.paths' => [
                ...config('inertia.pages.paths', []),
                realpath(__DIR__.'/../../../resources/js/Pages'),
                ...app(AdminPanel::class)->pagePaths(),
            ],
        ]);

        return parent::handle($request, $next);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $panel = app(AdminPanel::class);

        return [
            ...parent::share($request),
            'navigation' => fn (): array => $panel->navigationItems(),
            'admin' => function () use ($request): ?array {
                $admin = $request->user('larasell-admin');

                return $admin instanceof Admin ? [
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'avatarUrl' => $admin->avatarUrl(),
                ] : null;
            },
            'profileUrl' => fn (): string => route('larasell.admin.profile'),
            'logoutUrl' => fn (): string => route('larasell.admin.logout'),
        ];
    }
}
