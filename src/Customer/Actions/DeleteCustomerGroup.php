<?php

namespace Larasell\Admin\Customer\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Customer\AuditRecords\CustomerGroupDeleted;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Larasell\Customers\Actions\DeleteCustomerGroup as CoreDeleteCustomerGroup;
use Larasell\Larasell\Customers\CustomerGroupId;
use Larasell\Larasell\Customers\Exceptions\CustomerGroupInUse;
use Larasell\Larasell\Customers\Models\CustomerGroup;

/** Deletes an empty group through the core's action and audits it; a group with customers comes back as a validation error. */
class DeleteCustomerGroup
{
    public function __construct(private readonly CoreDeleteCustomerGroup $delete, private readonly Auditor $auditor) {}

    /** @throws ValidationException */
    public function handle(CustomerGroup $group): void
    {
        try {
            DB::transaction(function () use ($group): void {
                $this->delete->handle(CustomerGroupId::of($group));
                $this->auditor->record(new CustomerGroupDeleted($group->id, ['code' => $group->code]));
            });
        } catch (CustomerGroupInUse) {
            throw ValidationException::withMessages(['group' => __('Take the customers out of the group before deleting it.')]);
        }
    }
}
