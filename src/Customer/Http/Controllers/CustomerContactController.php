<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Customer\Http\Requests\CustomerContactRequest;
use Larasell\Larasell\Customers\Actions\AddCustomerContact;
use Larasell\Larasell\Customers\Actions\RemoveCustomerContact;
use Larasell\Larasell\Customers\Actions\UpdateCustomerContact;
use Larasell\Larasell\Customers\CustomerContactId;
use Larasell\Larasell\Customers\CustomerId;
use Larasell\Larasell\Customers\Exceptions\ContactsNeedACompany;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\Customers\Models\CustomerContact;

class CustomerContactController
{
    public function store(CustomerContactRequest $request, Customer $customer, AddCustomerContact $add): RedirectResponse
    {
        try {
            $add->handle(CustomerId::of($customer), $request->payload());
        } catch (ContactsNeedACompany) {
            throw ValidationException::withMessages(['contact' => __('Only companies have contacts.')]);
        }

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function update(CustomerContactRequest $request, Customer $customer, CustomerContact $contact, UpdateCustomerContact $update): RedirectResponse
    {
        $update->handle(CustomerContactId::of($contact), $request->payload());

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function destroy(Customer $customer, CustomerContact $contact, RemoveCustomerContact $remove): RedirectResponse
    {
        $remove->handle(CustomerContactId::of($contact));

        return redirect()->route('larasell.admin.customers.show', $customer);
    }
}
