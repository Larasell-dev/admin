<?php

namespace Larasell\Admin;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Inertia\ExceptionResponse;
use Larasell\Admin\ApiToken\ApiTokenServiceProvider;
use Larasell\Admin\Category\CategoryServiceProvider;
use Larasell\Admin\Customer\CustomerServiceProvider;
use Larasell\Admin\Order\OrderServiceProvider;
use Larasell\Admin\Platform\Audit\AuditServiceProvider;
use Larasell\Admin\Platform\Audit\Chain\Console\VerifyAuditLogCommand;
use Larasell\Admin\Platform\Audit\Console\MakeAuditRecordCommand;
use Larasell\Admin\Platform\Audit\Export\Console\ExportAuditLogCommand;
use Larasell\Admin\Platform\Audit\Log\Console\PruneAuditLogCommand;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\ContentLocale\Locales\AvailableLocales;
use Larasell\Admin\Platform\ContentLocale\Locales\EntitledLocales;
use Larasell\Admin\Platform\ContentLocale\Locales\StoreLocales;
use Larasell\Admin\Platform\ContentLocale\Resolvers\ContentLocaleResolver;
use Larasell\Admin\Platform\ContentLocale\Resolvers\SessionContentLocaleResolver;
use Larasell\Admin\Platform\InterfaceLocale\Http\Middleware\SetInterfaceLocale;
use Larasell\Admin\Platform\InterfaceLocale\Locales\AvailableInterfaceLocales;
use Larasell\Admin\Platform\InterfaceLocale\Locales\PanelInterfaceLocales;
use Larasell\Admin\Platform\InterfaceLocale\Resolvers\CookieInterfaceLocaleResolver;
use Larasell\Admin\Platform\InterfaceLocale\Resolvers\InterfaceLocaleResolver;
use Larasell\Admin\Platform\Navigation\Navigation;
use Larasell\Admin\Platform\Panel\Console\LinkAdminAssetsCommand;
use Larasell\Admin\Platform\Panel\Http\Middleware\HandleAdminInertiaRequests;
use Larasell\Admin\Platform\Panel\Http\Middleware\LogLicensingPlan;
use Larasell\Admin\Platform\Search\Searches;
use Larasell\Admin\Platform\Sections\Sections;
use Larasell\Admin\Platform\Settings\Settings;
use Larasell\Admin\Platform\Support\Assert;
use Larasell\Admin\Platform\Tables\Console\MakeTableCommand;
use Larasell\Admin\Platform\Tables\Http\Controllers\TableController;
use Larasell\Admin\Product\ProductServiceProvider;
use Larasell\Admin\SalesChannel\SalesChannelServiceProvider;
use Larasell\Admin\Seo\SeoServiceProvider;
use Larasell\Admin\Team\Console\CreateAdminUserCommand;
use Larasell\Admin\Team\Console\ListAdminsCommand;
use Larasell\Admin\Team\Console\RemoveAdminCommand;
use Larasell\Admin\Team\TeamServiceProvider;
use Larasell\Chronicle\ChronicleServiceProvider;
use Larasell\Larasell\Enums\Plan;
use Larasell\Larasell\Facades\Entitlement;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadServiceProvider;
use Laravel\Pennant\PennantServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/larasell/admin/panel.php', 'larasell.admin.panel');

        // Before the module providers, which boot first and register their routes with the macros.
        $this->registerRouteMacros();

        $this->app->register(HeadServiceProvider::class);
        $this->app->register(PennantServiceProvider::class);
        $this->app->register(ChronicleServiceProvider::class);
        $this->app->register(AuditServiceProvider::class);
        $this->app->register(ProductServiceProvider::class);
        $this->app->register(CategoryServiceProvider::class);
        $this->app->register(OrderServiceProvider::class);
        $this->app->register(CustomerServiceProvider::class);
        $this->app->register(ApiTokenServiceProvider::class);
        $this->app->register(TeamServiceProvider::class);
        $this->app->register(SalesChannelServiceProvider::class);
        $this->app->register(SeoServiceProvider::class);

        $this->app->singleton(AdminPanel::class);
        $this->app->singleton(Navigation::class);
        $this->app->singleton(Searches::class);
        $this->app->singleton(Sections::class);
        $this->app->singleton(Settings::class);
        // Reports each failed assertion once per process.
        $this->app->singleton(Assert::class);

        // Which locales content is edited in, and how a request picks one. Apps rebind them, e.g.
        // to take both from a sales channel.
        $this->app->bind(AvailableLocales::class, StoreLocales::class);
        $this->app->bind(ContentLocaleResolver::class, SessionContentLocaleResolver::class);
        // Extenders outlive rebinding, so apps' AvailableLocales are limited by the plan too.
        $this->app->extend(AvailableLocales::class, fn (AvailableLocales $locales): AvailableLocales => new EntitledLocales($locales));

        // Which languages the panel's own text is shown in, and how a request picks one. Apps
        // rebind them, e.g. to keep the pick on the admin's account.
        $this->app->bind(AvailableInterfaceLocales::class, PanelInterfaceLocales::class);
        $this->app->bind(InterfaceLocaleResolver::class, CookieInterfaceLocaleResolver::class);
    }

    public function boot(): void
    {
        $this->configureAuth();

        // Editing content in other locales than the default one.
        Entitlement::define(EntitledLocales::ENTITLEMENT, Plan::Pro);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'larasell-admin');
        // The panel's server text, keyed by its English text; an app's lang/<code>.json overrides it.
        $this->loadJsonTranslationsFrom(__DIR__.'/../lang');

        Route::middleware(['web', SetInterfaceLocale::class, LogLicensingPlan::class, HandleAdminInertiaRequests::class])
            ->prefix(config('larasell.admin.panel.path'))
            ->name('larasell.admin.')
            ->withHead(title: AdminPanel::TITLE)
            ->group(__DIR__.'/../routes/web.php');

        $this->renderErrorPage();

        $this->loadApplicationExtensions($this->app->make(AdminPanel::class));

        // Every provider and routes file has registered its navigation items by now.
        $this->app->booted(fn () => $this->app->make(Navigation::class)->freeze());

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/larasell/admin' => config_path('larasell/admin'),
            ], 'larasell-admin-config');

            $this->commands([
                CreateAdminUserCommand::class,
                ExportAuditLogCommand::class,
                LinkAdminAssetsCommand::class,
                ListAdminsCommand::class,
                MakeAuditRecordCommand::class,
                MakeTableCommand::class,
                PruneAuditLogCommand::class,
                VerifyAuditLogCommand::class,
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
     * Admin pages that fail with a 403, 404, 500 or 503, such as unknown admin addresses (the
     * fallback route) or editing an admin that does not exist, show the panel's error page:
     * inside the sidebar for signed-in admins, on its own for guests. Server errors keep
     * Laravel's stack trace while debugging. Other requests keep the application's response.
     *
     * A renderable rather than Inertia::handleExceptionsUsing(), which takes the exception
     * handler's only respondUsing() slot and so would replace the application's own callback.
     */
    private function renderErrorPage(): void
    {
        $this->callAfterResolving(ExceptionHandler::class, function (ExceptionHandler $handler): void {
            if (! $handler instanceof Handler) {
                return;
            }

            $handler->renderable(function (Throwable $exception, Request $request) {
                // The handler turns these into responses of their own, such as validation errors.
                if ($exception instanceof AuthenticationException
                    || $exception instanceof ValidationException
                    || $exception instanceof HttpResponseException) {
                    return null;
                }

                $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

                if (! in_array($status, [403, 404, 500, 503], true)
                    || ($status >= 500 && config('app.debug'))
                    || ! $request->isMethod('GET')
                    || $request->expectsJson()
                    || ! $request->route()?->named('larasell.admin.*')) {
                    return null;
                }

                Head::title(match ($status) {
                    403 => __('Access denied'),
                    404 => __('Page not found'),
                    500 => __('Something went wrong'),
                    503 => __('Temporarily unavailable'),
                });

                return (new ExceptionResponse($exception, $request, new Response(status: $status), app(Router::class), app(Kernel::class)))
                    ->usingMiddleware(HandleAdminInertiaRequests::class)
                    ->render(Auth::guard('larasell-admin')->check() ? 'Platform/Panel/Pages/Error' : 'Platform/Panel/Pages/GuestError', ['status' => $status])
                    ->withSharedData()
                    ->toResponse($request)
                    ->setStatusCode($status);
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
