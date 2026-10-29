<?php

namespace Larasell\Admin\ServiceProviders;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Http\Controllers\Orders\OrderController;
use Larasell\Admin\Tables\Orders\Orders;

/**
 * The orders module: its table page, its navigation item, and the page that shows an order.
 * Orders aren't edited, so there are no pages to change them. Setting
 * larasell.admin.orders.enabled to false leaves all of them out.
 */
class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/larasell/admin/orders.php', 'larasell.admin.orders');
    }

    public function boot(): void
    {
        if (! config('larasell.admin.orders.enabled')) {
            return;
        }

        Navigation::index(NavItem::Orders->label(), 'orders', Orders::class, icon: Icon::Receipt)->position(NavItem::Orders->position());

        AdminPanel::resolve()->routes(function (): void {
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        });
    }
}
