<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Larasell\Admin\Customer\Actions\ChangeCustomerStatus;
use Larasell\Larasell\Customers\Enums\CustomerStatus;
use Larasell\Larasell\Customers\Models\Customer;

/** Blocks, archives or reactivates a customer. */
class CustomerStatusController
{
    public function __invoke(Request $request, Customer $customer, ChangeCustomerStatus $change): RedirectResponse
    {
        $request->validate(['status' => ['required', Rule::enum(CustomerStatus::class)]]);
        $change->handle($customer, $request->enum('status', CustomerStatus::class) ?? CustomerStatus::Active);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }
}
