<?php

namespace Larasell\Admin\Customer\Actions;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Customer\AuditRecords\CustomerStatusChanged;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Larasell\Customers\Actions\ChangeCustomerStatus as CoreChangeCustomerStatus;
use Larasell\Larasell\Customers\CustomerId;
use Larasell\Larasell\Customers\Enums\CustomerStatus;
use Larasell\Larasell\Customers\Models\Customer;

/** Changes a customer's status through the core's action and audits it, from the page or the table. */
class ChangeCustomerStatus
{
    public function __construct(private readonly CoreChangeCustomerStatus $change, private readonly Auditor $auditor) {}

    public function handle(Customer $customer, CustomerStatus $status): void
    {
        $from = $customer->status;

        DB::transaction(function () use ($customer, $status, $from): void {
            $this->change->handle(CustomerId::of($customer), $status);

            if ($from !== $status) {
                $this->auditor->record(new CustomerStatusChanged($customer->id, ['from' => $from->value, 'to' => $status->value]));
            }
        });
    }
}
