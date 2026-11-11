<?php

namespace Larasell\Admin\Platform\Audit;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Platform\Audit\Export\Formats\AuditLogExportFormat;
use Larasell\Admin\Platform\Audit\Export\Formats\JsonFormat;
use Larasell\Admin\Platform\Audit\Export\Http\Controllers\AuditLogExportController;
use Larasell\Admin\Platform\Audit\Export\Listeners\CleanUpAuditLogExports;
use Larasell\Admin\Platform\Audit\Log\Http\Controllers\AuditLogController;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Settings\Facades\Settings;
use Larasell\Larasell\Audit\Events\AuditLogPruned;
use Laravel\Pennant\Feature;

/**
 * The audit log's pages: the settings page that lists the core's audit records, and their exports.
 *
 * Who may open the page is up to the larasell.admin.view-audit-log gate, and who may filter it
 * up to larasell.admin.search-audit-log, and who may export it up to
 * larasell.admin.export-audit-log. All let every admin by default. An app restricts them
 * by defining them again; its providers boot after this one, so its definition wins, e.g.
 * Gate::define('larasell.admin.search-audit-log', fn (Admin $admin) => $admin->isOwner()).
 *
 * The filters are also behind the audit-log-filters Pennant feature, checked per admin and
 * inactive by default. While it is inactive, the page shows no filters and ignores them in the
 * address. Pennant stores the value it resolves for each admin, so turn the feature on with
 * Feature::activateForEveryone('audit-log-filters') or Feature::for($admin)->activate(...);
 * redefining it only changes it for admins who haven't opened the page yet.
 *
 * @internal Registered by AdminServiceProvider. The gates' and the feature's names are public.
 */
class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../../config/larasell/admin/audit.php', 'larasell.admin.audit');

        $this->app->singleton(Origins::class);
        // The export's file format; an app binds its own to swap it.
        $this->app->bindIf(AuditLogExportFormat::class, JsonFormat::class);
    }

    public function boot(): void
    {
        Event::listen(AuditLogPruned::class, CleanUpAuditLogExports::class);

        Gate::define('larasell.admin.view-audit-log', fn (Admin $admin): bool => true);
        Gate::define('larasell.admin.search-audit-log', fn (Admin $admin): bool => true);
        Gate::define('larasell.admin.export-audit-log', fn (Admin $admin): bool => true);

        // Each admin's browsers hear when their own exports finish.
        Broadcast::channel('larasell-admin.audit-log-exports.{adminId}', fn (mixed $user, string $adminId): bool => $user instanceof Admin && (string) $user->getKey() === $adminId, ['guards' => ['larasell-admin']]);

        Feature::define('audit-log-filters', fn (Admin $admin): bool => false);

        // Registered before Team and API tokens, so it is placed after them by id.
        Settings::add('audit-log')
            ->label(Translate::later('Audit log'))
            ->description(Translate::later('Who changed what, and when'))
            ->icon(Icon::FileShield)
            ->route('larasell.admin.settings.audit-log.index')
            ->after('api-tokens');

        AdminPanel::resolve()->routes(function (): void {
            Route::get('settings/audit-log', [AuditLogController::class, 'index'])->name('settings.audit-log.index')->withHead(title: Translate::later('Audit log'));
            Route::post('settings/audit-log/exports', [AuditLogExportController::class, 'store'])->name('settings.audit-log.exports.store');
            Route::get('settings/audit-log/exports/{export}', [AuditLogExportController::class, 'show'])->name('settings.audit-log.exports.show')->middleware('signed');
            Route::delete('settings/audit-log/exports/{export}', [AuditLogExportController::class, 'destroy'])->name('settings.audit-log.exports.destroy');
        });
    }
}
