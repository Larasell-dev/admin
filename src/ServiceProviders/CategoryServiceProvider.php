<?php

namespace Larasell\Admin\ServiceProviders;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Http\Controllers\Categories\CategoryController;
use Larasell\Admin\Http\Controllers\Categories\CategoryProductController;
use Larasell\Admin\Http\Controllers\Categories\HideCategoryController;
use Larasell\Admin\Http\Controllers\Categories\MoveCategoryController;
use Larasell\Admin\Http\Controllers\Categories\SearchProductsNotInCategoryController;
use Larasell\Admin\Http\Controllers\Categories\UnhideCategoryController;

/**
 * The categories module: the category tree, its navigation item, and the pages to add, edit,
 * hide, move and delete a category and to add products to it.
 */
class CategoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Navigation::add(NavItem::Categories->label(), NavItem::Categories->value, icon: Icon::Folders)->position(NavItem::Categories->position());

        AdminPanel::resolve()->routes(function (): void {
            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index')->withHead(title: 'Categories');
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create')->withHead(title: 'New category');
            Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
            Route::get('categories/{category}/available-products', SearchProductsNotInCategoryController::class)->name('categories.available-products.index');
            Route::post('categories/{category}/products', [CategoryProductController::class, 'store'])->name('categories.products.store');
            Route::post('categories/{category}/hide', HideCategoryController::class)->name('categories.hide');
            Route::post('categories/{category}/unhide', UnhideCategoryController::class)->name('categories.unhide');
            Route::post('categories/{category}/move', MoveCategoryController::class)->name('categories.move');
        });
    }
}
