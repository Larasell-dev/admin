<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Customer\Http\Requests\CustomerContactRequest;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Larasell\Audit\Origin;
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
    public function store(CustomerContactRequest $request, Customer $customer, AddCustomerContact $add, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        try {
            $add->handle(CustomerId::of($customer), $request->payload(), $origin);
        } catch (ContactsNeedACompany) {
            throw ValidationException::withMessages(['contact' => __('Only companies have contacts.')]);
        }

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function update(CustomerContactRequest $request, Customer $customer, CustomerContact $contact, UpdateCustomerContact $update, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $update->handle(CustomerContactId::of($contact), $request->payload(), $origin);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function destroy(Customer $customer, CustomerContact $contact, RemoveCustomerContact $remove, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $remove->handle(CustomerContactId::of($contact), $origin);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }
}
