<?php

namespace Larasell\Admin\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Customers\Enums\AddressUse;

/** What an address becomes the customer's default for: billing or shipping. */
class DefaultCustomerAddressRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ['use' => ['required', Rule::enum(AddressUse::class)]];
    }

    /** What the core's SetDefaultCustomerAddress takes. */
    public function addressUse(): AddressUse
    {
        return $this->enum('use', AddressUse::class) ?? AddressUse::Billing;
    }
}
