<?php

namespace Larasell\Admin\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Larasell\Customers\Actions\CustomerContactPayload;

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
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'job_title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function payload(): CustomerContactPayload
    {
        return new CustomerContactPayload(
            firstName: $this->string('first_name')->value(),
            lastName: $this->string('last_name')->value(),
            email: $this->input('email'),
            phone: $this->input('phone'),
            jobTitle: $this->input('job_title'),
        );
    }
}
