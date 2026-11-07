<?php

namespace Larasell\Admin\SalesChannel;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inertia\OnceProp;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Search\Searches;
use Larasell\Admin\SalesChannel\Data\SalesChannelNavigationData;
use Larasell\Admin\SalesChannel\Events\SalesChannelsChanged;
use Larasell\Admin\SalesChannel\Http\Controllers\EntryPointController;
use Larasell\Admin\SalesChannel\Http\Controllers\SalesChannelController;
use Larasell\Admin\SalesChannel\Http\Controllers\SearchSalesChannelsController;
use Larasell\Admin\SalesChannel\Search\DatabaseSalesChannelSearch;
use Larasell\Admin\SalesChannel\Search\SalesChannelSearch;
use Larasell\Larasell\SalesChannels\Events\SalesChannelCreated;
use Larasell\Larasell\SalesChannels\Events\SalesChannelDeleted;
use Larasell\Larasell\SalesChannels\Events\SalesChannelUpdated;

/**
 * The sales channels module: the sidebar section that lists the channels, and the pages to add,
 * edit and delete one, with a storefront's domains, and its command menu search. Writes go through the core's sales channel actions.
 */
class SalesChannelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SalesChannelSearch::class, DatabaseSalesChannelSearch::class);
    }

    public function boot(): void
    {
        // A closure, so the session is read per request; guests on the sign-in page get none.
        AdminPanel::resolve()->share('salesChannels', fn (): ?OnceProp => auth('larasell-admin')->check() ? SalesChannelNavigationData::prop() : null);

        // Only what the sidebar shows: a channel's name, and whether it exists.
        Event::listen([SalesChannelCreated::class, SalesChannelDeleted::class], fn () => SalesChannelsChanged::dispatch());
        Event::listen(SalesChannelUpdated::class, function (SalesChannelUpdated $event): void {
            if (in_array('name', $event->changed, true)) {
                SalesChannelsChanged::dispatch();
            }
        });
        Broadcast::channel(SalesChannelsChanged::CHANNEL, fn (mixed $user): bool => $user instanceof Admin, ['guards' => ['larasell-admin']]);

        app(Searches::class)->add('sales-channels', Translate::later('Search sales channels'), Icon::Store, Translate::later('Search by name'), 'larasell.admin.search.sales-channels');

        AdminPanel::resolve()->routes(function (): void {
            Route::get('sales-channels/create', [SalesChannelController::class, 'create'])->name('sales-channels.create')->withHead(title: Translate::later('New sales channel'));
            Route::post('sales-channels', [SalesChannelController::class, 'store'])->name('sales-channels.store');
            Route::get('sales-channels/{channel}/edit', [SalesChannelController::class, 'edit'])->name('sales-channels.edit');
            Route::put('sales-channels/{channel}', [SalesChannelController::class, 'update'])->name('sales-channels.update');
            Route::delete('sales-channels/{channel}', [SalesChannelController::class, 'destroy'])->name('sales-channels.destroy');
            Route::post('sales-channels/{channel}/entry-points', [EntryPointController::class, 'store'])->name('sales-channels.entry-points.store');
            Route::delete('sales-channels/{channel}/entry-points/{entryPoint}', [EntryPointController::class, 'destroy'])->whereNumber('entryPoint')->name('sales-channels.entry-points.destroy');
            Route::get('search/sales-channels', SearchSalesChannelsController::class)->name('search.sales-channels');
        });
    }
}
