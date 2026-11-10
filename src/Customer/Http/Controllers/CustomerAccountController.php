<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Larasell\Admin\Customer\AuditRecords\CustomerAccountUnlinked;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Larasell\Customers\Actions\UnlinkCustomerAccount;
use Larasell\Larasell\Customers\CustomerAccountId;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\Customers\Models\CustomerAccount;

/** Unlinks a user from a customer. Linking happens in the app's own sign-up or sign-in flow. */
class CustomerAccountController
{
    public function destroy(Customer $customer, CustomerAccount $account, UnlinkCustomerAccount $unlink, Auditor $auditor): RedirectResponse
    {
        DB::transaction(function () use ($customer, $account, $unlink, $auditor): void {
            $unlink->handle(CustomerAccountId::of($account));
            $auditor->record(new CustomerAccountUnlinked($customer->id, ['account_type' => $account->account_type, 'account_id' => $account->account_id]));
        });

        return redirect()->route('larasell.admin.customers.show', $customer);
    }
}
