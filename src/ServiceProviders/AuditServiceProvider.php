<?php

namespace Larasell\Admin\ServiceProviders;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\Audit\Auditor;

/**
 * The audit log: the Auditor that modules and extensions record their audit records with.
 *
 * @internal Registered by AdminServiceProvider.
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
    }
}
