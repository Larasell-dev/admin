<?php

namespace Larasell\Admin;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Larasell\Admin\Console\CreateAdminUserCommand;
use Larasell\Admin\Console\LinkAdminAssetsCommand;
use Larasell\Admin\Console\ListAdminsCommand;
use Larasell\Admin\Console\MakeTableCommand;
use Larasell\Admin\Console\RemoveAdminCommand;
use Larasell\Admin\Http\Controllers\TableController;
use Larasell\Admin\Http\Middleware\HandleAdminInertiaRequests;
use Larasell\Admin\Models\Admin;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadServiceProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admin.php', 'larasell-admin');

        $this->app->register(HeadServiceProvider::class);

        $this->app->singleton(AdminPanel::class);
        $this->app->singleton(Navigation::class);
    }

    public function boot(): void
    {
        $this->configureAuth();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'larasell-admin');

        Route::middleware(['web', HandleAdminInertiaRequests::class])
            ->prefix(config('larasell-admin.path'))
            ->name('larasell.admin.')
            ->withHead(title: AdminPanel::TITLE)
            ->group(__DIR__.'/../routes/web.php');

        $this->registerRouteMacros();
        $this->renderNotFoundPage();

        $this->loadApplicationExtensions($this->app->make(AdminPanel::class));

        // Every provider and routes file has registered its navigation items by now.
        $this->app->booted(fn () => $this->app->make(Navigation::class)->freeze());

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateAdminUserCommand::class,
                LinkAdminAssetsCommand::class,
                ListAdminsCommand::class,
                MakeTableCommand::class,
                RemoveAdminCommand::class,
            ]);
        }
    }

    private function loadApplicationExtensions(AdminPanel $panel): void
    {
        $pages = base_path('resources/js/admin/pages');

        if (is_dir($pages)) {
            $panel->pagePath($pages);
        }

        if (is_file(base_path('resources/js/admin/app.tsx'))) {
            $panel->script('resources/js/admin/app.tsx');
        }

        $routes = base_path('routes/admin.php');

        if (is_file($routes)) {
            $panel->routes(function () use ($routes): void {
                require $routes;
            });
        }
    }

    private function registerRouteMacros(): void
    {
        Route::macro('index', function (string $uri, string $table): RouteDefinition {
            return Route::get($uri, TableController::class)
                ->defaults('table', $table)
                ->name(str_replace('/', '.', trim($uri, '/')).'.index');
        });
    }

    /**
     * Signed-in admins see the panel's 404 page for admin pages that 404, such as editing an
     * admin that does not exist, and for addresses without an admin route (the fallback route).
     */
    private function renderNotFoundPage(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(function (NotFoundHttpException $e, Request $request) {
                if (! $request->isMethod('GET')
                    || $request->expectsJson()
                    || ! $request->route()?->named('larasell.admin.*')
                    || ! Auth::guard('larasell-admin')->check()) {
                    return null;
                }

                // Route model binding 404s before the Inertia middleware runs, so run it here.
                return app(HandleAdminInertiaRequests::class)->handle($request, function () use ($request) {
                    Head::title('Page not found');

                    // Inertia still renders a page that comes with an error status.
                    return Inertia::render('NotFound')->toResponse($request)->setStatusCode(404);
                });
            });
        });
    }

    private function configureAuth(): void
    {
        config([
            'auth.guards.larasell-admin' => [
                'driver' => 'session',
                'provider' => 'larasell-admin-users',
            ],
            'auth.providers.larasell-admin-users' => [
                'driver' => 'eloquent',
                'model' => Admin::class,
            ],
        ]);
    }
}
