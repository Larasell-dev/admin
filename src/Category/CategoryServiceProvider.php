<?php

namespace Larasell\Admin\Category;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Category\Enums\CategorySection;
use Larasell\Admin\Category\Http\Controllers\CategoryController;
use Larasell\Admin\Category\Http\Controllers\CategoryProductController;
use Larasell\Admin\Category\Http\Controllers\HideCategoryController;
use Larasell\Admin\Category\Http\Controllers\MoveCategoryController;
use Larasell\Admin\Category\Http\Controllers\SearchCategoriesController;
use Larasell\Admin\Category\Http\Controllers\SearchProductsNotInCategoryController;
use Larasell\Admin\Category\Http\Controllers\UnhideCategoryController;
use Larasell\Admin\Category\Search\CategorySearch;
use Larasell\Admin\Category\Search\DatabaseCategorySearch;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Navigation\Facades\Navigation;
use Larasell\Admin\Platform\Navigation\NavItem;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Search\Searches;
use Larasell\Admin\Platform\Sections\Facades\Section;
use Larasell\Admin\Platform\Sections\SectionForm;

/**
 * The categories module: the category tree, its navigation item, and the pages to add, edit,
 * hide, move and delete a category and to add products to it. Setting
 * larasell.admin.categories.enabled to false leaves all of them out; products can still be put
 * into categories.
 */
class CategoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/larasell/admin/categories.php', 'larasell.admin.categories');

        $this->app->bind(CategorySearch::class, DatabaseCategorySearch::class);
    }

    public function boot(): void
    {
        if (! config('larasell.admin.categories.enabled')) {
            return;
        }

        // Sections that modules and plugins add to the category form go before or after these.
        Section::anchors(SectionForm::Category, CategorySection::class);

        Navigation::add(NavItem::Categories->label(), NavItem::Categories->value, icon: Icon::Folders)->position(NavItem::Categories->position());

        app(Searches::class)->add('categories', Translate::later('Search categories'), Icon::Folders, Translate::later('Search by name'), 'larasell.admin.search.categories');

        AdminPanel::resolve()->routes(function (): void {
            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index')->withHead(title: Translate::later('Categories'));
            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create')->withHead(title: Translate::later('New category'));
            Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
            Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
            Route::get('categories/{category}/available-products', SearchProductsNotInCategoryController::class)->name('categories.available-products.index');
            Route::post('categories/{category}/products', [CategoryProductController::class, 'store'])->name('categories.products.store');
            Route::post('categories/{category}/hide', HideCategoryController::class)->name('categories.hide');
            Route::post('categories/{category}/unhide', UnhideCategoryController::class)->name('categories.unhide');
            Route::post('categories/{category}/move', MoveCategoryController::class)->name('categories.move');
            Route::get('search/categories', SearchCategoriesController::class)->name('search.categories');
        });
    }
}
