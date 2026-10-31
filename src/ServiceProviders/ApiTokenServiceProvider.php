<?php

namespace Larasell\Admin\ServiceProviders;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Http\Controllers\ApiTokens\ApiTokenController;
use Larasell\Admin\Http\Middleware\ApiTokens\AuthenticateApiToken;

/**
 * The API tokens module: the settings pages to list, create and revoke tokens, their navigation
 * item under Settings, and the larasell.api middleware that extensions put their API behind.
 */
class ApiTokenServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        AuthenticateApiToken::register();

        // After Admins, which routes/web.php adds once every module has booted.
        Navigation::add('API tokens', 'larasell.admin.settings.api-tokens.index', Icon::Key)
            ->under(NavItem::Settings)
            ->after('larasell.admin.settings.admins.index');

        AdminPanel::resolve()->routes(function (): void {
            Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])->name('settings.api-tokens.index')->withHead(title: 'API tokens');
            Route::post('settings/api-tokens', [ApiTokenController::class, 'store'])->name('settings.api-tokens.store');
            Route::get('settings/api-tokens/create', [ApiTokenController::class, 'create'])->name('settings.api-tokens.create')->withHead(title: 'New API token');
        });
    }
}
