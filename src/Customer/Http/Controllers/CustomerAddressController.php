<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Customer\Http\Requests\CustomerAddressRequest;
use Larasell\Admin\Customer\Http\Requests\DefaultCustomerAddressRequest;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Customers\Actions\AddCustomerAddress;
use Larasell\Larasell\Customers\Actions\DeleteCustomerAddress;
use Larasell\Larasell\Customers\Actions\SetDefaultCustomerAddress;
use Larasell\Larasell\Customers\Actions\UpdateCustomerAddress;
use Larasell\Larasell\Customers\CustomerAddressId;
use Larasell\Larasell\Customers\CustomerId;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\Customers\Models\CustomerAddress;

/** A customer's addresses. The routes bind the address within its customer, so another's is a 404. */
class CustomerAddressController
{
    public function store(CustomerAddressRequest $request, Customer $customer, AddCustomerAddress $add, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $add->handle(CustomerId::of($customer), $request->payload(), $origin);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function update(CustomerAddressRequest $request, Customer $customer, CustomerAddress $address, UpdateCustomerAddress $update, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $update->handle(CustomerAddressId::of($address), $request->payload(), $origin);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function destroy(Customer $customer, CustomerAddress $address, DeleteCustomerAddress $delete, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $delete->handle(CustomerAddressId::of($address), $origin);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function makeDefault(DefaultCustomerAddressRequest $request, Customer $customer, CustomerAddress $address, SetDefaultCustomerAddress $setDefault, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $setDefault->handle(CustomerAddressId::of($address), $request->addressUse(), $origin);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }
}
