<?php

namespace Larasell\Admin\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;
use Larasell\Larasell\Customers\Models\CustomerGroup;

/**
 * Rules for prices or catalogs may refer to a group by its code, so the admin types the code to
 * delete it.
 */
class DeleteCustomerGroupRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $group = $this->route('group');
        assert($group instanceof CustomerGroup);

        return ['confirmation' => [new Confirmation($group->code)]];
    }
}
