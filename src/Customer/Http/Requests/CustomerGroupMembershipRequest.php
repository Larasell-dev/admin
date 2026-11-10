<?php

namespace Larasell\Admin\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Customers\CustomerGroupId;
use Larasell\Larasell\Customers\Models\CustomerGroup;

/** The group an admin adds a customer to. */
class CustomerGroupMembershipRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ['group' => ['required', 'integer', Rule::exists(CustomerGroup::class, 'id')]];
    }

    /** What the core's AddCustomerToGroup takes. */
    public function groupId(): CustomerGroupId
    {
        return new CustomerGroupId($this->integer('group'));
    }
}
