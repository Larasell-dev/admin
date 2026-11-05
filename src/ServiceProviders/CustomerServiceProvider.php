<?php

namespace Larasell\Admin\ServiceProviders;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Customer\Search\CustomerSearch;
use Larasell\Admin\Customer\Search\DatabaseCustomerSearch;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Http\Controllers\Customers\CustomerController;
use Larasell\Admin\Http\Controllers\Customers\SearchCustomersController;
use Larasell\Admin\Search\Searches;
use Larasell\Admin\Tables\Customers\Customers;

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

        app(Searches::class)->add('customers', 'Search customers', Icon::Group, 'Search by name', 'larasell.admin.search.customers');

        AdminPanel::resolve()->routes(function (): void {
            Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create')->withHead(title: 'New customer');
            Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
            Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
            Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
            Route::get('search/customers', SearchCustomersController::class)->name('search.customers');
        });
    }
}
