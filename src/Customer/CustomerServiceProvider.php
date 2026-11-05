<?php

namespace Larasell\Admin\Customer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Customer\Http\Controllers\CustomerController;
use Larasell\Admin\Customer\Http\Controllers\SearchCustomersController;
use Larasell\Admin\Customer\Search\CustomerSearch;
use Larasell\Admin\Customer\Search\DatabaseCustomerSearch;
use Larasell\Admin\Customer\Tables\Customers;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Navigation\Facades\Navigation;
use Larasell\Admin\Platform\Navigation\NavItem;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Search\Searches;

/**
 * The customers module: its table page, its navigation item, and the pages to add and edit a
 * customer. Setting larasell.admin.customers.enabled to false leaves all of them out.
 */
class CustomerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/larasell/admin/customers.php', 'larasell.admin.customers');

        $this->app->bind(CustomerSearch::class, DatabaseCustomerSearch::class);
    }

    public function boot(): void
    {
        if (! config('larasell.admin.customers.enabled')) {
            return;
        }

        Navigation::index(NavItem::Customers->label(), 'customers', Customers::class, icon: Icon::Group)->position(NavItem::Customers->position());

        app(Searches::class)->add('customers', Translate::later('Search customers'), Icon::Group, Translate::later('Search by name'), 'larasell.admin.search.customers');

        AdminPanel::resolve()->routes(function (): void {
            Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create')->withHead(title: Translate::later('New customer'));
            Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
            Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
            Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
            Route::get('search/customers', SearchCustomersController::class)->name('search.customers');
        });
    }
}
