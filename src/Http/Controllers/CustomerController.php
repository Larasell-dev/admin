<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\CreateCustomerPageData;
use Larasell\Admin\Data\View\CustomersPageData;
use Larasell\Admin\Data\View\EditCustomerPageData;
use Larasell\Admin\Data\ViewModel\CustomerData;
use Larasell\Admin\Data\ViewModel\CustomerDetailData;
use Larasell\Admin\Data\ViewModel\PaginatedCustomersData;
use Larasell\Admin\Http\Requests\CustomerRequest;
use Larasell\Larasell\Models\Customer;
use Laravel\Head\Facades\Head;

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
            createUrl: route('larasell.admin.customers.create'),
            customers: PaginatedCustomersData::fromPaginator($customers),
        ));
    }

    public function create(): Response
    {
        return Inertia::render('Customer/Create', new CreateCustomerPageData(
            storeUrl: route('larasell.admin.customers.store'),
            indexUrl: route('larasell.admin.customers.index'),
        ));
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = Customer::query()->create($request->validated());

        return redirect()->route('larasell.admin.customers.edit', $customer);
    }

    public function edit(Customer $customer): Response
    {
        Head::title($customer->fullName());

        return Inertia::render('Customer/Edit', new EditCustomerPageData(
            customer: CustomerDetailData::fromModel($customer),
            updateUrl: route('larasell.admin.customers.update', $customer),
            indexUrl: route('larasell.admin.customers.index'),
        ));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('larasell.admin.customers.edit', $customer);
    }
}
