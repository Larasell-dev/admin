<?php

namespace Larasell\Admin;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Route;
use Larasell\Admin\Http\Middleware\Authenticate;
use Larasell\Admin\Http\Middleware\HandleAdminInertiaRequests;
use Larasell\Admin\Navigation\NavigationItem;

class AdminPanel
{
    /**
     * @var list<NavigationItem>
     */
    protected array $navigation = [];

    /**
     * @var list<string>
     */
    protected array $scripts = [];

    /**
     * @var list<string>
     */
    protected array $pagePaths = [];

    public static function resolve(): self
    {
        return app(self::class);
    }

    public function add(string $label, string $route): void
    {
        if (! str_contains($route, '.')) {
            $route = 'larasell.admin.'.$route;
        }

        $this->navigation[] = new NavigationItem($label, $route);
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public function navigationItems(): array
    {
        return array_map(
            fn (NavigationItem $item): array => $item->toArray(),
            $this->navigation,
        );
    }

    public function routes(Closure $routes): void
    {
        Route::middleware(['web', HandleAdminInertiaRequests::class, Authenticate::class])
            ->prefix(config('larasell-admin.path'))
            ->name('larasell.admin.')
            ->group($routes);
    }

    public function script(string $input): void
    {
        $this->scripts[] = $input;
    }

    /**
     * @return list<string>
     */
    public function scripts(): array
    {
        return $this->scripts;
    }

    public function pagePath(string $path): void
    {
        $this->pagePaths[] = $path;
    }

    /**
     * @return list<string>
     */
    public function pagePaths(): array
    {
        return $this->pagePaths;
    }

    public function panelVite(): Vite
    {
        return (clone app(Vite::class))
            ->useBuildDirectory('vendor/larasell-admin')
            ->useHotFile(dirname(__DIR__).'/public/hot');
    }
}
