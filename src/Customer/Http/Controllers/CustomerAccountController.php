<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Larasell\Customers\Actions\UnlinkCustomerAccount;
use Larasell\Larasell\Customers\CustomerAccountId;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\Customers\Models\CustomerAccount;

/** Unlinks a user from a customer. Linking happens in the app's own sign-up or sign-in flow. */
class CustomerAccountController
{
    public function destroy(Customer $customer, CustomerAccount $account, UnlinkCustomerAccount $unlink): RedirectResponse
    {
        $unlink->handle(CustomerAccountId::of($account));

        return redirect()->route('larasell.admin.customers.show', $customer);
    }
}
