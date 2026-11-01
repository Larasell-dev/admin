<?php

namespace Larasell\Admin\ServiceProviders;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Http\Controllers\AuditLog\AuditLogController;
use Larasell\Admin\Models\Admin;

/**
 * The audit log: the Auditor that modules and extensions record their audit records with, and
 * the settings page that lists them.
 *
 * Who may open the page is up to the larasell.admin.view-audit-log gate, and who may filter it
 * up to larasell.admin.search-audit-log. Both let every admin by default. An app restricts them
 * by defining them again; its providers boot after this one, so its definition wins, e.g.
 * Gate::define('larasell.admin.search-audit-log', fn (Admin $admin) => $admin->isOwner()).
 *
 * @internal Registered by AdminServiceProvider. The gates' names are public.
 */
class AuditServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Auditor::class);
    }

    public function boot(): void
    {
        // Tells the Auditor when a queued job runs, so its records get the job source and must name
        // their actor. Otherwise a job on the sync queue would run inside the request that dispatched
        // it and be recorded as the signed-in admin. A job ends with exactly one of JobProcessed and
        // JobExceptionOccurred.
        Event::listen(JobProcessing::class, fn () => $this->app->make(Auditor::class)->jobStarted());
        Event::listen([JobProcessed::class, JobExceptionOccurred::class], fn () => $this->app->make(Auditor::class)->jobEnded());

        Gate::define('larasell.admin.view-audit-log', fn (Admin $admin): bool => true);
        Gate::define('larasell.admin.search-audit-log', fn (Admin $admin): bool => true);

        // After API tokens, or Admins when that module is turned off.
        Navigation::add('Audit log', 'larasell.admin.settings.audit-log.index', Icon::FileShield)
            ->under(NavItem::Settings)
            ->after(config('larasell.admin.api_tokens.enabled') ? 'larasell.admin.settings.api-tokens.index' : 'larasell.admin.settings.admins.index');

        AdminPanel::resolve()->routes(function (): void {
            Route::get('settings/audit-log', [AuditLogController::class, 'index'])->name('settings.audit-log.index')->withHead(title: 'Audit log');
        });
    }
}
