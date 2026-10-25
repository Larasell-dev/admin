<?php

namespace Larasell\Admin\ServiceProviders;

use Illuminate\Support\ServiceProvider;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Tables\Product\Products;

/**
 * The products module. Setting larasell.admin.products.enabled to false leaves
 * out its routes and navigation item.
 */
class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/larasell/admin/products.php', 'larasell.admin.products');
    }

    public function boot(): void
    {
        if (! config('larasell.admin.products.enabled')) {
            return;
        }

        Navigation::index(NavItem::Products->label(), 'products', Products::class, icon: Icon::ShoppingBag)->position(NavItem::Products->position());
    }
}
