<?php

namespace Larasell\Admin\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Customers\Actions\CompanyProfileData;
use Larasell\Larasell\Customers\Actions\PersonProfileData;
use Larasell\Larasell\Customers\Enums\CustomerType;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\Enums\Country;
use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Enums\Locale;
use Larasell\Admin\Customer\Rules\ValidPhoneNumber;
use Larasell\Admin\Customer\Rules\ValidUrl;
use Larasell\Larasell\EmailAddress;
use Larasell\Larasell\PhoneNumber;
use Larasell\Larasell\Url;

/**
 * A customer's profile and preferences. On create the type comes from the form; on update it is
 * the customer's own, since it can't change.
 */
class CustomerProfileRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $type = $this->customerType()?->value;

        return [
            'type' => [Rule::requiredIf($this->route('customer') === null), Rule::enum(CustomerType::class)],
            'first_name' => [Rule::requiredIf($type === 'individual'), 'nullable', 'string', 'max:255'],
            'last_name' => [Rule::requiredIf($type === 'individual'), 'nullable', 'string', 'max:255'],
            'salutation' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email:filter', 'max:255'],
            'phone' => ['nullable', 'string', new ValidPhoneNumber],
            'legal_name' => [Rule::requiredIf($type === 'company'), 'nullable', 'string', 'max:255'],
            'legal_form' => ['nullable', 'string', 'max:64'],
            'company_number' => ['nullable', 'string', 'max:64'],
            'vat_id' => ['nullable', 'string', 'max:64'],
            'country_code' => [Rule::requiredIf($type === 'company'), 'nullable', Rule::enum(Country::class)],
            'industry' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255', new ValidUrl],
            'preferred_locale' => ['nullable', Rule::enum(Locale::class)],
            'preferred_currency' => ['nullable', Rule::enum(Currency::class)],
        ];
    }

    public function customerType(): ?CustomerType
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer ? $customer->type : CustomerType::tryFrom($this->string('type')->value());
    }

    public function profile(): PersonProfileData|CompanyProfileData
    {
        if ($this->customerType() === CustomerType::Company) {
            return new CompanyProfileData(
                legalName: $this->string('legal_name')->value(),
                countryCode: $this->enum('country_code', Country::class) ?? Country::DE,
                vatId: $this->input('vat_id'),
                legalForm: $this->input('legal_form'),
                companyNumber: $this->input('company_number'),
                industry: $this->input('industry'),
                website: $this->filled('website') ? Url::of($this->string('website')->value()) : null,
            );
        }

        return new PersonProfileData(
            firstName: $this->string('first_name')->value(),
            lastName: $this->string('last_name')->value(),
            email: $this->filled('email') ? EmailAddress::of($this->string('email')->value()) : null,
            phone: $this->filled('phone') ? PhoneNumber::of($this->string('phone')->value()) : null,
            salutation: $this->input('salutation'),
        );
    }

    public function preferredLocale(): ?Locale
    {
        return $this->enum('preferred_locale', Locale::class);
    }

    public function preferredCurrency(): ?Currency
    {
        return $this->enum('preferred_currency', Currency::class);
    }
}
