<?php

namespace Larasell\Admin;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inertia\ExceptionResponse;
use Larasell\Admin\Console\CreateAdminUserCommand;
use Larasell\Admin\Console\LinkAdminAssetsCommand;
use Larasell\Admin\Console\ListAdminsCommand;
use Larasell\Admin\Console\MakeTableCommand;
use Larasell\Admin\Console\RemoveAdminCommand;
use Larasell\Admin\Http\Controllers\TableController;
use Larasell\Admin\Http\Middleware\HandleAdminInertiaRequests;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\ServiceProviders\CustomerServiceProvider;
use Larasell\Admin\ServiceProviders\OrderServiceProvider;
use Larasell\Admin\ServiceProviders\ProductServiceProvider;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/larasell/admin/panel.php', 'larasell.admin.panel');

        // Before the module providers, which boot first and register their routes with the macros.
        $this->registerRouteMacros();

        $this->app->register(HeadServiceProvider::class);
        $this->app->register(ProductServiceProvider::class);
        $this->app->register(CustomerServiceProvider::class);
        $this->app->register(OrderServiceProvider::class);

        $this->app->singleton(AdminPanel::class);
        $this->app->singleton(Navigation::class);
    }

    public function boot(): void
    {
        $this->configureAuth();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'larasell-admin');

        Route::middleware(['web', HandleAdminInertiaRequests::class])
            ->prefix(config('larasell.admin.panel.path'))
            ->name('larasell.admin.')
            ->withHead(title: AdminPanel::TITLE)
            ->group(__DIR__.'/../routes/web.php');

        $this->renderNotFoundPage();

        $this->loadApplicationExtensions($this->app->make(AdminPanel::class));

        // Every provider and routes file has registered its navigation items by now.
        $this->app->booted(fn () => $this->app->make(Navigation::class)->freeze());

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/larasell/admin' => config_path('larasell/admin'),
            ], 'larasell-admin-config');

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
     *
     * A renderable rather than Inertia::handleExceptionsUsing(), which takes the exception
     * handler's only respondUsing() slot and so would replace the application's own callback.
     */
    private function renderNotFoundPage(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(function (HttpExceptionInterface $exception, Request $request) {
                if ($exception->getStatusCode() !== 404
                    || ! $request->isMethod('GET')
                    || $request->expectsJson()
                    || ! $request->route()?->named('larasell.admin.*')) {
                    return null;
                }

                Head::title('Page not found');

                return (new ExceptionResponse($exception, $request, new Response(status: 404), app(Router::class), app(Kernel::class)))
                    ->usingMiddleware(HandleAdminInertiaRequests::class)
                    ->render(Auth::guard('larasell-admin')->check() ? 'NotFound' : 'GuestNotFound')
                    ->withSharedData()
                    ->toResponse($request);
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
