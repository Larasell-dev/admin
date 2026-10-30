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
     * The prebuilt admin assets of extensions, by the name their build is linked under.
     *
     * @var array<string, array{publicPath: string, entries: list<string>}>
     */
    protected array $extensions = [];

    /** Whether routes() is running its callback, so a nested call doesn't open the group again. */
    private bool $registeringRoutes = false;

    public static function resolve(): self
    {
        return app(self::class);
    }

    /**
     * Registers admin routes in the admin route group. Inside that group already, e.g. when
     * routes/admin.php calls Navigation::index(), it adds them as they are, so the prefixes
     * don't apply twice.
     */
    public function routes(Closure $routes): void
    {
        if ($this->registeringRoutes) {
            $routes();

            return;
        }

        $this->registeringRoutes = true;

        try {
            Route::middleware(['web', HandleAdminInertiaRequests::class, Authenticate::class])
                ->prefix(config('larasell.admin.panel.path'))
                ->name('larasell.admin.')
                ->withHead(title: self::TITLE)
                ->group($routes);
        } finally {
            $this->registeringRoutes = false;
        }
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
     * Loads an extension's prebuilt scripts and stylesheets on every admin page. Its Vite build,
     * made with the larasell() plugin under the same $name, sits in $publicPath/vendor/{$name}
     * next to the dev server's hot file; larasell:link links it into the app's public/vendor.
     *
     * @param  list<string>  $entries
     */
    public function vite(string $name, string $publicPath, array $entries): void
    {
        $this->extensions[$name] = ['publicPath' => rtrim($publicPath, '/'), 'entries' => $entries];
    }

    /**
     * @return array<string, array{publicPath: string, entries: list<string>}>
     */
    public function extensions(): array
    {
        return $this->extensions;
    }

    /**
     * The Vite instance for an extension's assets, reading its own build and hot file.
     */
    public function extensionVite(string $name): Vite
    {
        $extension = $this->extensions[$name] ?? throw new InvalidArgumentException("No extension assets are registered as [{$name}].");

        return (clone app(Vite::class))
            ->useBuildDirectory("vendor/{$name}")
            ->useHotFile("{$extension['publicPath']}/hot");
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
