<?php

namespace Larasell\Admin\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Tables\Orders\Orders;

/**
 * The orders module: its table page and its navigation item. Setting
 * larasell.admin.orders.enabled to false leaves both out.
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
    }
}
