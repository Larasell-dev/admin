<?php

namespace Larasell\Admin\Brand;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Brand\Http\Controllers\BrandController;
use Larasell\Admin\Brand\Http\Controllers\SearchBrandsController;
use Larasell\Admin\Brand\Tables\Brands;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Search\Searches;
use Larasell\Admin\Platform\Settings\Facades\Settings;

/**
 * The brands module: the brands table, the pages to add and edit a brand, and the command menu
 * search. Products pick their brand on the product form. Writes go through the core's brand actions.
 */
class BrandServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Only products pick them, so they go with them.
        if (! config('larasell.admin.products.enabled')) {
            return;
        }

        Settings::add('brands')
            ->label(Translate::later('Brands'))
            ->description(Translate::later('The names products are marketed under'))
            ->icon(Icon::Award)
            ->route('larasell.admin.brands.index')
            ->after('audit-log');

        app(Searches::class)->add('brands', Translate::later('Search brands'), Icon::Award, Translate::later('Search by name or slug'), 'larasell.admin.search.brands');

        AdminPanel::resolve()->routes(function (): void {
            Route::index('brands', Brands::class);
            Route::get('brands/create', [BrandController::class, 'create'])->name('brands.create')->withHead(title: Translate::later('New brand'));
            Route::post('brands', [BrandController::class, 'store'])->name('brands.store');
            Route::post('brands/validate', [BrandController::class, 'check'])->name('brands.validate');
            Route::get('brands/{brand}', [BrandController::class, 'show'])->whereNumber('brand')->name('brands.show');
            Route::put('brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
            Route::delete('brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
            Route::get('search/brands', SearchBrandsController::class)->name('search.brands');
        });
    }
}
