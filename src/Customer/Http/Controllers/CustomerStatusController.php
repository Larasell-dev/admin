<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Customer\Http\Requests\CustomerStatusRequest;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Customers\Actions\ChangeCustomerStatus;
use Larasell\Larasell\Customers\CustomerId;
use Larasell\Larasell\Customers\Models\Customer;

/** Blocks, archives or reactivates a customer. */
class CustomerStatusController
{
    public function __invoke(CustomerStatusRequest $request, Customer $customer, ChangeCustomerStatus $change, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $change->handle(CustomerId::of($customer), $request->status(), $origin);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }
}
