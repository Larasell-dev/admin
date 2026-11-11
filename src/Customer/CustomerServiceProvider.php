<?php

namespace Larasell\Admin\Customer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Customer\Http\Controllers\CustomerAccountController;
use Larasell\Admin\Customer\Http\Controllers\CustomerAddressController;
use Larasell\Admin\Customer\Http\Controllers\CustomerContactController;
use Larasell\Admin\Customer\Http\Controllers\CustomerController;
use Larasell\Admin\Customer\Http\Controllers\CustomerGroupController;
use Larasell\Admin\Customer\Http\Controllers\CustomerGroupMembershipController;
use Larasell\Admin\Customer\Http\Controllers\CustomerStatusController;
use Larasell\Admin\Customer\Http\Controllers\SearchCustomersController;
use Larasell\Admin\Customer\Search\CustomerSearch;
use Larasell\Admin\Customer\Search\DatabaseCustomerSearch;
use Larasell\Admin\Customer\Tables\CustomerGroups;
use Larasell\Admin\Customer\Tables\Customers;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Navigation\Facades\Navigation;
use Larasell\Admin\Platform\Navigation\NavItem;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Search\Searches;

/**
 * The customers module: the customers and customer groups tables, the pages to add and edit a
 * customer with its addresses, contacts, linked accounts and groups, and the command menu search.
 * Writes go through the core's customer actions. Accounts are only unlinked here: the app links
 * them in its own sign-up or sign-in flow. Setting larasell.admin.customers.enabled to false
 * leaves all of it out.
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

        Navigation::index(NavItem::Customers->label(), 'customers', Customers::class, icon: Icon::User3)->position(NavItem::Customers->position());
        Navigation::index(Translate::later('Customer groups'), 'customer-groups', CustomerGroups::class, icon: Icon::Group)->under(NavItem::Customers);

        app(Searches::class)->add('customers', Translate::later('Search customers'), Icon::User3, Translate::later('Search by number, name or email'), 'larasell.admin.search.customers');

        AdminPanel::resolve()->routes(function (): void {
            Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create')->withHead(title: Translate::later('New customer'));
            Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
            Route::get('customers/{customer}', [CustomerController::class, 'show'])->whereNumber('customer')->name('customers.show');
            Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
            Route::post('customers/{customer}/status', CustomerStatusController::class)->name('customers.status');

            Route::scopeBindings()->group(function (): void {
                Route::post('customers/{customer}/addresses', [CustomerAddressController::class, 'store'])->name('customers.addresses.store');
                Route::put('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update'])->name('customers.addresses.update');
                Route::delete('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('customers.addresses.destroy');
                Route::post('customers/{customer}/addresses/{address}/default', [CustomerAddressController::class, 'makeDefault'])->name('customers.addresses.default');
                Route::post('customers/{customer}/contacts', [CustomerContactController::class, 'store'])->name('customers.contacts.store');
                Route::put('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'update'])->name('customers.contacts.update');
                Route::delete('customers/{customer}/contacts/{contact}', [CustomerContactController::class, 'destroy'])->name('customers.contacts.destroy');
                Route::delete('customers/{customer}/accounts/{account}', [CustomerAccountController::class, 'destroy'])->name('customers.accounts.destroy');
                Route::post('customers/{customer}/groups', [CustomerGroupMembershipController::class, 'store'])->name('customers.groups.store');
                Route::delete('customers/{customer}/groups/{group}', [CustomerGroupMembershipController::class, 'destroy'])->name('customers.groups.destroy');
            });

            Route::get('customer-groups/create', [CustomerGroupController::class, 'create'])->name('customer-groups.create')->withHead(title: Translate::later('New customer group'));
            Route::post('customer-groups', [CustomerGroupController::class, 'store'])->name('customer-groups.store');
            Route::get('customer-groups/{group}/edit', [CustomerGroupController::class, 'edit'])->name('customer-groups.edit');
            Route::put('customer-groups/{group}', [CustomerGroupController::class, 'update'])->name('customer-groups.update');
            Route::delete('customer-groups/{group}', [CustomerGroupController::class, 'destroy'])->name('customer-groups.destroy');

            Route::get('search/customers', SearchCustomersController::class)->name('search.customers');
        });
    }
}
