<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Larasell\Admin\Customer\Http\Requests\CustomerAddressRequest;
use Larasell\Larasell\Customers\Actions\AddCustomerAddress;
use Larasell\Larasell\Customers\Actions\DeleteCustomerAddress;
use Larasell\Larasell\Customers\Actions\SetDefaultCustomerAddress;
use Larasell\Larasell\Customers\Actions\UpdateCustomerAddress;
use Larasell\Larasell\Customers\CustomerAddressId;
use Larasell\Larasell\Customers\CustomerId;
use Larasell\Larasell\Customers\Enums\AddressUse;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\Customers\Models\CustomerAddress;

/** A customer's addresses. The routes bind the address within its customer, so another's is a 404. */
class CustomerAddressController
{
    public function store(CustomerAddressRequest $request, Customer $customer, AddCustomerAddress $add): RedirectResponse
    {
        $add->handle(CustomerId::of($customer), $request->payload());

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function update(CustomerAddressRequest $request, Customer $customer, CustomerAddress $address, UpdateCustomerAddress $update): RedirectResponse
    {
        $update->handle(CustomerAddressId::of($address), $request->payload());

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function destroy(Customer $customer, CustomerAddress $address, DeleteCustomerAddress $delete): RedirectResponse
    {
        $delete->handle(CustomerAddressId::of($address));

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function makeDefault(Request $request, Customer $customer, CustomerAddress $address, SetDefaultCustomerAddress $setDefault): RedirectResponse
    {
        $request->validate(['use' => ['required', Rule::enum(AddressUse::class)]]);
        $setDefault->handle(CustomerAddressId::of($address), $request->enum('use', AddressUse::class) ?? AddressUse::Billing);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }
}
