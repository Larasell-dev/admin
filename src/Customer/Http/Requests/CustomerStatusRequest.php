<?php

namespace Larasell\Admin\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Customers\Enums\CustomerStatus;

/** The status an admin picked in the customer page's status menu. */
class CustomerStatusRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(CustomerStatus::class)]];
    }

    /** What the core's ChangeCustomerStatus takes. */
    public function status(): CustomerStatus
    {
        return $this->enum('status', CustomerStatus::class) ?? CustomerStatus::Active;
    }
}
