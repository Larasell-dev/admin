<?php

namespace Larasell\Admin\Customer\Actions;

use Illuminate\Validation\ValidationException;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Customers\Actions\DeleteCustomerGroup as CoreDeleteCustomerGroup;
use Larasell\Larasell\Customers\CustomerGroupId;
use Larasell\Larasell\Customers\Exceptions\CustomerGroupInUse;

/**
 * Deletes an empty group through the core's action, from the page or the table; a group with
 * customers comes back as a validation error.
 */
class DeleteCustomerGroup
{
    public function __construct(private readonly CoreDeleteCustomerGroup $delete) {}

    /** @throws ValidationException */
    public function handle(CustomerGroupId $groupId, Origin $origin): void
    {
        try {
            $this->delete->handle($groupId, $origin);
        } catch (CustomerGroupInUse) {
            throw ValidationException::withMessages(['group' => __('Take the customers out of the group before deleting it.')]);
        }
    }
}
