<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Customer\Http\Requests\CustomerGroupMembershipRequest;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Customers\Actions\AddCustomerToGroup;
use Larasell\Larasell\Customers\Actions\RemoveCustomerFromGroup;
use Larasell\Larasell\Customers\CustomerGroupId;
use Larasell\Larasell\Customers\CustomerId;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\Customers\Models\CustomerGroup;

class CustomerGroupMembershipController
{
    public function store(CustomerGroupMembershipRequest $request, Customer $customer, AddCustomerToGroup $add, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $add->handle(CustomerId::of($customer), $request->groupId(), $origin);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function destroy(Customer $customer, CustomerGroup $group, RemoveCustomerFromGroup $remove, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $remove->handle(CustomerId::of($customer), CustomerGroupId::of($group), $origin);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }
}
