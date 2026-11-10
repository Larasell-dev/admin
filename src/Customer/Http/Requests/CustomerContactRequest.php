<?php

namespace Larasell\Admin\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Admin\Customer\Rules\ValidPhoneNumber;
use Larasell\Larasell\Customers\Actions\CustomerContactPayload;
use Larasell\Larasell\EmailAddress;
use Larasell\Larasell\PhoneNumber;

class CustomerContactRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email:filter', 'max:255'],
            'phone' => ['nullable', 'string', new ValidPhoneNumber],
            'job_title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function payload(): CustomerContactPayload
    {
        return new CustomerContactPayload(
            firstName: $this->string('first_name')->value(),
            lastName: $this->string('last_name')->value(),
            email: $this->filled('email') ? EmailAddress::of($this->string('email')->value()) : null,
            phone: $this->filled('phone') ? PhoneNumber::of($this->string('phone')->value()) : null,
            jobTitle: $this->input('job_title'),
        );
    }
}
