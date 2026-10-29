<?php

namespace Larasell\Admin;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
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

    /**
     * Prebuilt scripts that packages ship, by the name their assets are linked under.
     *
     * @var array<string, array{publicPath: string, entry: string}>
     */
    protected array $packageScripts = [];

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

    /**
     * Loads a package's prebuilt script on every admin page, e.g. one that registers its pages.
     * Like the panel's own assets, $publicPath holds the build in vendor/{$name} and the dev
     * server's hot file; larasell:link links the build into the app's public/vendor/{$name}.
     */
    public function packageScript(string $name, string $publicPath, string $entry): void
    {
        $this->packageScripts[$name] = ['publicPath' => rtrim($publicPath, '/'), 'entry' => $entry];
    }

    /**
     * @return array<string, array{publicPath: string, entry: string}>
     */
    public function packageScripts(): array
    {
        return $this->packageScripts;
    }

    /**
     * The Vite instance for a package's script, reading the package's own build and hot file.
     */
    public function packageVite(string $name): Vite
    {
        $script = $this->packageScripts[$name] ?? throw new InvalidArgumentException("No package script is registered as [{$name}].");

        return (clone app(Vite::class))
            ->useBuildDirectory("vendor/{$name}")
            ->useHotFile("{$script['publicPath']}/hot");
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
