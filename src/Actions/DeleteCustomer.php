<?php

namespace Larasell\Admin\Actions;

use Illuminate\Support\Facades\DB;
use Larasell\Larasell\Models\Customer;

class DeleteCustomer
{
    /**
     * Deletes the customer and unlinks their user accounts. Their orders stay, with their own
     * copy of the customer's name and email, but no longer point at the deleted customer.
     */
    public function handle(Customer $customer): void
    {
        DB::transaction(function () use ($customer): void {
            $customer->orders()->update(['customer_id' => null]);
            $customer->users()->detach();
            $customer->delete();
        });
    }
}
