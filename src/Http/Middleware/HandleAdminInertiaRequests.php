<?php

namespace Larasell\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\ContentLocale\ContentLocaleResolver;
use Larasell\Admin\Data\ViewModel\ContentLocale\ContentLocaleData;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Navigation;
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
        return [
            ...parent::share($request),
            'navigation' => fn (): array => app(Navigation::class)->tree(),
            'admin' => function () use ($request): ?array {
                $admin = $request->user('larasell-admin');

                return $admin instanceof Admin ? [
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'avatarUrl' => $admin->avatarUrl(),
                ] : null;
            },
            // On every page, so the command menu can switch it anywhere, e.g. on a table it names.
            'contentLocale' => fn (): ?ContentLocaleData => $request->user('larasell-admin') instanceof Admin
                ? ContentLocaleData::of(app(ContentLocaleResolver::class)->resolve($request))
                : null,
            // The command menu's searches, of the modules that are turned on.
            'searchUrls' => fn (): ?array => $request->user('larasell-admin') instanceof Admin
                ? collect(['products', 'categories', 'orders', 'customers'])
                    ->filter(fn (string $resource): bool => Route::has("larasell.admin.search.{$resource}"))
                    ->mapWithKeys(fn (string $resource): array => [$resource => route("larasell.admin.search.{$resource}")])
                    ->all()
                : null,
            'profileUrl' => fn (): string => route('larasell.admin.profile'),
            'logoutUrl' => fn (): string => route('larasell.admin.logout'),
            'loginUrl' => fn (): string => route('larasell.admin.login.store'),
        ];
    }
}
