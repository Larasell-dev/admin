<?php

namespace Larasell\Admin\SalesChannel;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inertia\OnceProp;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\SalesChannel\Data\SalesChannelNavigationData;
use Larasell\Admin\SalesChannel\Http\Controllers\EntryPointController;
use Larasell\Admin\SalesChannel\Http\Controllers\SalesChannelController;

/**
 * The sales channels module: the sidebar section that lists the channels, and the pages to add,
 * edit and delete one, with a storefront's domains. Writes go through the core's sales channel actions.
 */
class SalesChannelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // A closure, so the session is read per request; guests on the sign-in page get none.
        AdminPanel::resolve()->share('salesChannels', fn (): ?OnceProp => auth('larasell-admin')->check() ? SalesChannelNavigationData::prop() : null);

        AdminPanel::resolve()->routes(function (): void {
            Route::get('sales-channels/create', [SalesChannelController::class, 'create'])->name('sales-channels.create')->withHead(title: Translate::later('New sales channel'));
            Route::post('sales-channels', [SalesChannelController::class, 'store'])->name('sales-channels.store');
            Route::get('sales-channels/{channel}/edit', [SalesChannelController::class, 'edit'])->name('sales-channels.edit');
            Route::put('sales-channels/{channel}', [SalesChannelController::class, 'update'])->name('sales-channels.update');
            Route::delete('sales-channels/{channel}', [SalesChannelController::class, 'destroy'])->name('sales-channels.destroy');
            Route::post('sales-channels/{channel}/entry-points', [EntryPointController::class, 'store'])->name('sales-channels.entry-points.store');
            Route::delete('sales-channels/{channel}/entry-points/{entryPoint}', [EntryPointController::class, 'destroy'])->whereNumber('entryPoint')->name('sales-channels.entry-points.destroy');
        });
    }
}
