<?php

namespace Larasell\Admin\Http\Controllers\Customers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\Customers\CreateCustomerPageData;
use Larasell\Admin\Data\View\Customers\EditCustomerPageData;
use Larasell\Admin\Data\ViewModel\Customers\CustomerDetailData;
use Larasell\Admin\Http\Requests\Customers\CustomerRequest;
use Larasell\Admin\Tables\Customers\CustomerOrders;
use Larasell\Larasell\Models\Customer;
use Laravel\Head\Facades\Head;

class CustomerController
{
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
            orders: CustomerOrders::make($customer),
        ));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('larasell.admin.customers.edit', $customer);
    }
}
