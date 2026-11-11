<?php

namespace Larasell\Admin\Product;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Navigation\Facades\Navigation;
use Larasell\Admin\Platform\Navigation\NavItem;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Search\Searches;
use Larasell\Admin\Platform\Sections\Facades\Section;
use Larasell\Admin\Platform\Sections\SectionForm;
use Larasell\Admin\Product\Enums\ProductSection;
use Larasell\Admin\Product\Http\Controllers\ProductController;
use Larasell\Admin\Product\Http\Controllers\SearchProductsController;
use Larasell\Admin\Product\Search\DatabaseProductSearch;
use Larasell\Admin\Product\Search\ProductSearch;
use Larasell\Admin\Product\Tables\Products;

/**
 * The products module. Setting larasell.admin.products.enabled to false leaves
 * out its routes and navigation item.
 */
class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/larasell/admin/products.php', 'larasell.admin.products');

        $this->app->bind(ProductSearch::class, DatabaseProductSearch::class);
    }

    public function boot(): void
    {
        if (! config('larasell.admin.products.enabled')) {
            return;
        }

        // Sections that modules and plugins add to the product form go before or after these.
        Section::anchors(SectionForm::Product, ProductSection::class);

        Navigation::index(NavItem::Products->label(), 'products', Products::class, icon: Icon::ShoppingBag)->position(NavItem::Products->position());

        app(Searches::class)->add('products', Translate::later('Search products'), Icon::ShoppingBag, Translate::later('Search by name, SKU or barcode'), 'larasell.admin.search.products');

        AdminPanel::resolve()->routes(function (): void {
            Route::get('products/create', [ProductController::class, 'create'])->name('products.create')->withHead(title: Translate::later('New product'));
            Route::post('products', [ProductController::class, 'store'])->name('products.store');
            Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::get('search/products', SearchProductsController::class)->name('search.products');
        });
    }
}
