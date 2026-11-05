<?php

namespace Larasell\Admin\ApiToken;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\ApiToken\Http\Controllers\ApiTokenController;
use Larasell\Admin\ApiToken\Http\Middleware\AuthenticateApiToken;
use Larasell\Admin\ApiToken\Models\ApiToken;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Navigation\Facades\Navigation;
use Larasell\Admin\Platform\Navigation\NavItem;
use Larasell\Admin\Platform\Panel\Icon;

/**
 * The API tokens module: the settings pages to list, create and revoke tokens, their navigation
 * item under Settings, and the larasell.api middleware that extensions put their API behind.
 * Setting larasell.admin.api_tokens.enabled to false leaves out the pages and navigation item,
 * and the middleware then rejects every request.
 */
class ApiTokenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/larasell/admin/api_tokens.php', 'larasell.admin.api_tokens');
    }

    public function boot(): void
    {
        // Even when disabled, so extension routes behind larasell.api answer 401 rather than fail.
        AuthenticateApiToken::register();

        // Even when disabled, so the audit log still names the tokens of older records.
        $auditor = $this->app->make(Auditor::class);
        $auditor->apiGuard(AuthenticateApiToken::GUARD);
        $auditor->actorType('api_token', 'API token', ApiToken::class);

        if (! config('larasell.admin.api_tokens.enabled')) {
            return;
        }

        // After Team.
        Navigation::add('API tokens', 'larasell.admin.settings.api-tokens.index', Icon::Key)
            ->under(NavItem::Settings)
            ->after('larasell.admin.settings.team.index');

        AdminPanel::resolve()->routes(function (): void {
            Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])->name('settings.api-tokens.index')->withHead(title: 'API tokens');
            Route::post('settings/api-tokens', [ApiTokenController::class, 'store'])->name('settings.api-tokens.store');
            Route::get('settings/api-tokens/create', [ApiTokenController::class, 'create'])->name('settings.api-tokens.create')->withHead(title: 'New API token');
        });
    }
}
