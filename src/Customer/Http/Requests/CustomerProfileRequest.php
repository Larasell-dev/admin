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

/**
 * A customer's profile and preferences. On create the type comes from the form; on update it is
 * the customer's own, since it can't change.
 */
class CustomerProfileRequest extends FormRequest
{
    /** The form shows https:// in front of the website, so it sends only the rest. */
    protected function prepareForValidation(): void
    {
        $website = trim($this->string('website')->value());

        if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
            $this->merge(['website' => 'https://'.$website]);
        }
    }

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
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'legal_name' => [Rule::requiredIf($type === 'company'), 'nullable', 'string', 'max:255'],
            'legal_form' => ['nullable', 'string', 'max:64'],
            'company_number' => ['nullable', 'string', 'max:64'],
            'vat_id' => ['nullable', 'string', 'max:64'],
            'country_code' => [Rule::requiredIf($type === 'company'), 'nullable', Rule::enum(Country::class)],
            'industry' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
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
                website: $this->input('website'),
            );
        }

        return new PersonProfileData(
            firstName: $this->string('first_name')->value(),
            lastName: $this->string('last_name')->value(),
            email: $this->input('email'),
            phone: $this->input('phone'),
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
