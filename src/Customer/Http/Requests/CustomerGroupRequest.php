<?php

namespace Larasell\Admin\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Customers\Actions\CustomerGroupPayload;
use Larasell\Larasell\Customers\CustomerGroupCode;
use Larasell\Larasell\Customers\CustomerGroupDescription;
use Larasell\Larasell\Customers\Models\CustomerGroup;
use Larasell\Larasell\Translatable;

class CustomerGroupRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $group = $this->route('group');

        return [
            // Lowercase words joined by - or _, e.g. "wholesale-premium", since code refers to the group by it.
            'code' => ['required', 'string', 'max:'.CustomerGroupCode::MAX_LENGTH, 'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/',
                Rule::unique(CustomerGroup::class, 'code')->ignore($group instanceof CustomerGroup ? $group->id : null)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:'.CustomerGroupDescription::MAX_LENGTH],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => __('Use lowercase letters and numbers, joined by - or _.'),
            'code.unique' => __('A customer group with the code [:input] already exists.'),
        ];
    }

    /** The name in the interface language; other languages of an existing group are kept. */
    public function payload(?CustomerGroup $group = null): CustomerGroupPayload
    {
        $text = $this->string('name')->value();

        return new CustomerGroupPayload(
            code: CustomerGroupCode::of($this->string('code')->value()),
            name: $group?->name->with(app()->getLocale(), $text) ?? Translatable::fromString($text, app()->getLocale()),
            description: $this->filled('description') ? CustomerGroupDescription::of($this->string('description')->value()) : null,
        );
    }
}
