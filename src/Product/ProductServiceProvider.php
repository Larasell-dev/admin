<?php

namespace Larasell\Admin\Product;

use Illuminate\Support\Facades\Event;
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
use Larasell\Admin\Product\Http\Controllers\AttributeController;
use Larasell\Admin\Product\Http\Controllers\CatalogDefinitionController;
use Larasell\Admin\Product\Http\Controllers\ProductController;
use Larasell\Admin\Product\Http\Controllers\ProductVariantController;
use Larasell\Admin\Product\Http\Controllers\SearchProductsController;
use Larasell\Admin\Product\Listeners\AuditAttributeChanges;
use Larasell\Admin\Product\Search\DatabaseProductSearch;
use Larasell\Admin\Product\Search\ProductSearch;
use Larasell\Admin\Product\Tables\Products;
use Larasell\Larasell\Catalog\Events\ProductAttributeCreated;
use Larasell\Larasell\Catalog\Events\ProductAttributeDeleted;
use Larasell\Larasell\Catalog\Events\ProductAttributeUpdated;
use Larasell\Larasell\Catalog\Events\ProductAttributeValueCreated;
use Larasell\Larasell\Catalog\Events\ProductAttributeValueDeleted;
use Larasell\Larasell\Catalog\Events\ProductAttributeValueUpdated;

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
        // Audited from the core's events, wherever the change came from.
        Event::listen(ProductAttributeCreated::class, [AuditAttributeChanges::class, 'created']);
        Event::listen(ProductAttributeUpdated::class, [AuditAttributeChanges::class, 'updated']);
        Event::listen(ProductAttributeDeleted::class, [AuditAttributeChanges::class, 'deleted']);
        Event::listen(ProductAttributeValueCreated::class, [AuditAttributeChanges::class, 'valueCreated']);
        Event::listen(ProductAttributeValueUpdated::class, [AuditAttributeChanges::class, 'valueUpdated']);
        Event::listen(ProductAttributeValueDeleted::class, [AuditAttributeChanges::class, 'valueDeleted']);

        if (! config('larasell.admin.products.enabled')) {
            return;
        }

        // Sections that modules and plugins add to the product form go before or after these.
        Section::anchors(SectionForm::Product, ProductSection::class);

        Navigation::index(NavItem::Products->label(), 'products', Products::class, icon: Icon::ShoppingBag)->position(NavItem::Products->position());
        Navigation::add(Translate::later('Options'), 'larasell.admin.options.index', icon: Icon::ListCheck)->under(NavItem::Products);
        Navigation::add(Translate::later('Attributes'), 'larasell.admin.attributes.index', icon: Icon::PriceTag3)->under(NavItem::Products);

        app(Searches::class)->add('products', Translate::later('Search products'), Icon::ShoppingBag, Translate::later('Search by name, SKU or barcode'), 'larasell.admin.search.products');

        AdminPanel::resolve()->routes(function (): void {
            Route::get('products/create', [ProductController::class, 'create'])->name('products.create')->withHead(title: Translate::later('New product'));
            Route::post('products', [ProductController::class, 'store'])->name('products.store');
            Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
            Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::post('products/{product}/options', [ProductVariantController::class, 'storeOption'])->name('products.options.store');
            Route::post('products/{product}/options/{definition}/values', [ProductVariantController::class, 'storeOptionValue'])->name('products.options.values.store');
            Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
            Route::get('products/{product}/variants/{variant}/edit', [ProductVariantController::class, 'edit'])->name('products.variants.edit');
            Route::put('products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('products.variants.update');
            Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('products.variants.destroy');
            Route::get('options', [CatalogDefinitionController::class, 'options'])->name('options.index')->withHead(title: Translate::later('Options'));
            Route::post('options', [CatalogDefinitionController::class, 'storeOption'])->name('options.store');
            Route::post('options/{definition}/values', [CatalogDefinitionController::class, 'storeOptionValue'])->name('options.values.store');
            Route::get('attributes', [AttributeController::class, 'index'])->name('attributes.index')->withHead(title: Translate::later('Attributes'));
            Route::get('attributes/create', [AttributeController::class, 'create'])->name('attributes.create')->withHead(title: Translate::later('New attribute'));
            Route::post('attributes', [AttributeController::class, 'store'])->name('attributes.store');
            Route::get('attributes/{attribute}', [AttributeController::class, 'show'])->name('attributes.show');
            Route::put('attributes/{attribute}', [AttributeController::class, 'update'])->name('attributes.update');
            Route::delete('attributes/{attribute}', [AttributeController::class, 'destroy'])->name('attributes.destroy');
            Route::get('search/products', SearchProductsController::class)->name('search.products');
        });
    }
}
