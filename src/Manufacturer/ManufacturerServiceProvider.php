<?php

namespace Larasell\Admin\Manufacturer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Manufacturer\Http\Controllers\ManufacturerController;
use Larasell\Admin\Manufacturer\Http\Controllers\SearchManufacturersController;
use Larasell\Admin\Manufacturer\Tables\Manufacturers;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Search\Searches;
use Larasell\Admin\Platform\Settings\Facades\Settings;

/**
 * The manufacturers module: the legal producers that product pages in the EU name under the GPSR,
 * with their table, the pages to add and edit one, and the command menu search. Products pick
 * theirs on the product form. Writes go through the core's manufacturer actions.
 */
class ManufacturerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Only products pick them, so they go with them.
        if (! config('larasell.admin.products.enabled')) {
            return;
        }

        Settings::add('manufacturers')
            ->label(Translate::later('Manufacturers'))
            ->description(Translate::later('The legal producers product pages name (GPSR)'))
            ->icon(Icon::Building)
            ->route('larasell.admin.manufacturers.index')
            ->after('brands');

        app(Searches::class)->add('manufacturers', Translate::later('Search manufacturers'), Icon::Building, Translate::later('Search by name or email'), 'larasell.admin.search.manufacturers');

        AdminPanel::resolve()->routes(function (): void {
            Route::index('manufacturers', Manufacturers::class);
            Route::get('manufacturers/create', [ManufacturerController::class, 'create'])->name('manufacturers.create')->withHead(title: Translate::later('New manufacturer'));
            Route::post('manufacturers', [ManufacturerController::class, 'store'])->name('manufacturers.store');
            Route::post('manufacturers/validate', [ManufacturerController::class, 'check'])->name('manufacturers.validate');
            Route::get('manufacturers/{manufacturer}', [ManufacturerController::class, 'show'])->whereNumber('manufacturer')->name('manufacturers.show');
            Route::put('manufacturers/{manufacturer}', [ManufacturerController::class, 'update'])->name('manufacturers.update');
            Route::delete('manufacturers/{manufacturer}', [ManufacturerController::class, 'destroy'])->name('manufacturers.destroy');
            Route::get('search/manufacturers', SearchManufacturersController::class)->name('search.manufacturers');
        });
    }
}
