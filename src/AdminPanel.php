<?php

namespace Larasell\Admin;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Route;
use Larasell\Admin\Http\Middleware\Authenticate;
use Larasell\Admin\Http\Middleware\HandleAdminInertiaRequests;

class AdminPanel
{
    /**
     * Base page title of every admin route. Pages that set their own title
     * read "Categories - Admin"; pages without one read "Admin".
     */
    public const TITLE = ['value' => 'Admin', 'suffix' => ' - Admin', 'exact' => true];

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

    public function routes(Closure $routes): void
    {
        Route::middleware(['web', HandleAdminInertiaRequests::class, Authenticate::class])
            ->prefix(config('larasell.admin.panel.path'))
            ->name('larasell.admin.')
            ->withHead(title: self::TITLE)
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
