<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Customers\Actions\AddCustomerToGroup;
use Larasell\Larasell\Customers\Actions\RemoveCustomerFromGroup;
use Larasell\Larasell\Customers\CustomerGroupId;
use Larasell\Larasell\Customers\CustomerId;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\Customers\Models\CustomerGroup;

class CustomerGroupMembershipController
{
    public function store(Request $request, Customer $customer, AddCustomerToGroup $add): RedirectResponse
    {
        $request->validate([
            'group' => ['required', 'integer', Rule::exists(CustomerGroup::class, 'id')],
        ]);

        $add->handle(
            CustomerId::of($customer),
            new CustomerGroupId($request->integer('group')),
        );

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function destroy(Customer $customer, CustomerGroup $group, RemoveCustomerFromGroup $remove): RedirectResponse
    {
        $remove->handle(CustomerId::of($customer), CustomerGroupId::of($group));

        return redirect()->route('larasell.admin.customers.show', $customer);
    }
}
