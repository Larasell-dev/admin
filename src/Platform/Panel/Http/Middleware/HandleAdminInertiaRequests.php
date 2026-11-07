<?php

namespace Larasell\Admin\Platform\Panel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\ContentLocale\Data\ContentLocaleData;
use Larasell\Admin\Platform\ContentLocale\Resolvers\ContentLocaleResolver;
use Larasell\Admin\Platform\InterfaceLocale\Data\InterfaceLocaleData;
use Larasell\Admin\Platform\InterfaceLocale\Locales\AvailableInterfaceLocales;
use Larasell\Admin\Platform\InterfaceLocale\Resolvers\InterfaceLocaleResolver;
use Larasell\Admin\Platform\Navigation\Navigation;
use Larasell\Admin\Platform\Panel\Data\CommandMenuData;
use Larasell\Admin\Platform\Search\Searches;
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
                realpath(__DIR__.'/../../../../../resources/js/Pages'),
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
            'interfaceLocale' => fn (): ?InterfaceLocaleData => $request->user('larasell-admin') instanceof Admin
                ? InterfaceLocaleData::of(app(InterfaceLocaleResolver::class)->resolve($request), app(AvailableInterfaceLocales::class))
                : null,
            'commandMenu' => fn (): ?CommandMenuData => $request->user('larasell-admin') instanceof Admin
                ? new CommandMenuData(app(Searches::class)->all())
                : null,
            'profileUrl' => fn (): string => route('larasell.admin.profile'),
            'logoutUrl' => fn (): string => route('larasell.admin.logout'),
            'loginUrl' => fn (): string => route('larasell.admin.login.store'),
            ...app(AdminPanel::class)->shared(),
        ];
    }
}
