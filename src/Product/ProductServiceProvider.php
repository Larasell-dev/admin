<?php

namespace Larasell\Admin\Product;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Product\Http\Controllers\ProductController;

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

        $this->app->make(AdminPanel::class)->routes(function (): void {
            Route::get('products', [ProductController::class, 'index'])->name('products.index')->withHead(title: 'Products');
        });

        Navigation::add(NavItem::Products->label(), NavItem::Products->value, icon: Icon::ShoppingBag)->position(NavItem::Products->position());
    }
}
