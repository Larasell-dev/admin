<?php

namespace Larasell\Admin;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\Console\CreateAdminUserCommand;
use Larasell\Admin\Console\LinkAdminAssetsCommand;
use Larasell\Admin\Http\Middleware\HandleAdminInertiaRequests;
use Larasell\Admin\Models\Admin;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/admin.php', 'larasell-admin');

        $this->app->singleton(AdminPanel::class);
    }

    public function boot(): void
    {
        $this->configureAuth();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'larasell-admin');

        Route::middleware(['web', HandleAdminInertiaRequests::class])
            ->prefix(config('larasell-admin.path'))
            ->name('larasell.admin.')
            ->group(__DIR__.'/../routes/web.php');

        $this->loadApplicationExtensions($this->app->make(AdminPanel::class));

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateAdminUserCommand::class,
                LinkAdminAssetsCommand::class,
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

    private function configureAuth(): void
    {
        $guard = config('larasell-admin.guard');
        $provider = $guard.'-users';

        config([
            "auth.guards.{$guard}" => [
                'driver' => 'session',
                'provider' => $provider,
            ],
            "auth.providers.{$provider}" => [
                'driver' => 'eloquent',
                'model' => Admin::class,
            ],
        ]);
    }
}
