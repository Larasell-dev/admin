<?php

namespace Larasell\Admin;

use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inertia\ExceptionResponse;
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
     * Admin pages that 404, such as unknown admin addresses (the fallback route) or editing an
     * admin that does not exist, show the panel's 404 page: inside the sidebar for signed-in
     * admins, on its own for guests. Other requests keep the application's 404.
     */
    private function renderNotFoundPage(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $request = $response->request;

            if ($response->statusCode() !== 404
                || ! $request->isMethod('GET')
                || $request->expectsJson()
                || ! $request->route()?->named('larasell.admin.*')) {
                return null;
            }

            Head::title('Page not found');

            return $response
                ->usingMiddleware(HandleAdminInertiaRequests::class)
                ->render(Auth::guard('larasell-admin')->check() ? 'NotFound' : 'GuestNotFound')
                ->withSharedData();
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
