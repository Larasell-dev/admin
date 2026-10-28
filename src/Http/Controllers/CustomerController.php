<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\CustomersPageData;
use Larasell\Admin\Data\ViewModel\CustomerData;
use Larasell\Admin\Data\ViewModel\PaginatedCustomersData;
use Larasell\Larasell\Models\Customer;

class CustomerController
{
    public function index(Request $request): Response
    {
        $perPage = in_array($request->integer('per_page'), [25, 50, 100], true) ? $request->integer('per_page') : 25;

        $customers = Customer::query()
            ->withCount('orders')
            ->latest()
            ->paginate($perPage)
            ->through(fn (Customer $customer): CustomerData => CustomerData::fromModel($customer));

        return Inertia::render('Customer/Index', new CustomersPageData(
            customers: PaginatedCustomersData::fromPaginator($customers),
        ));
    }
}
