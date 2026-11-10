<?php

namespace Larasell\Admin\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Customers\Actions\CustomerAddressPayload;
use Larasell\Larasell\Enums\Country;

class CustomerAddressRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'street' => ['required', 'array', 'min:1', 'max:4'],
            'street.*' => ['required', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:32'],
            'city' => ['required', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'country_code' => ['required', Rule::enum(Country::class)],
            'phone' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function payload(): CustomerAddressPayload
    {
        return new CustomerAddressPayload(
            firstName: $this->string('first_name')->value(),
            lastName: $this->string('last_name')->value(),
            street: array_values(array_map('strval', (array) $this->input('street'))),
            postcode: $this->string('postcode')->value(),
            city: $this->string('city')->value(),
            countryCode: $this->enum('country_code', Country::class) ?? Country::DE,
            company: $this->input('company'),
            region: $this->input('region'),
            phone: $this->input('phone'),
        );
    }
}
