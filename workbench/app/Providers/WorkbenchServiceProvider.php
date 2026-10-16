<?php

namespace Workbench\App\Providers;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Amp orb portals terminate TLS and forward over HTTP; trusting the proxy
        // makes generated asset URLs use https instead of being blocked as mixed content.
        TrustProxies::at('*');

        // APP_URL is localhost, which a portal visitor's browser can't reach;
        // a relative URL serves public uploads from whatever host the page uses.
        config(['filesystems.disks.public.url' => '/storage']);

        // Portals open at the site root; send it to the panel.
        Route::redirect('/', '/'.config('larasell-admin.path'));
    }
}
