<?php

namespace Larasell\Admin\ServiceProviders;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Http\Controllers\Customers\CustomerController;
use Larasell\Admin\Tables\Customers\Customers;

/**
 * The customers module: its table page, its navigation item, and the pages to add and edit a
 * customer.
 */
class CustomerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Navigation::index(NavItem::Customers->label(), 'customers', Customers::class, icon: Icon::Group)->position(NavItem::Customers->position());

        AdminPanel::resolve()->routes(function (): void {
            Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create')->withHead(title: 'New customer');
            Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
            Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
            Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        });
    }
}
