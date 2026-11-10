<?php

namespace Larasell\Admin\Customer\Data;

use Larasell\Larasell\Customers\Models\Customer;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CustomerDetailData extends Data
{
    public function __construct(
        public int $id,
        public string $number,
        public string $name,
        /** individual or company */
        public string $type,
        /** active, blocked or archived */
        public string $status,
        /** storefront, pos, admin, import or api */
        public string $source,
        public ?string $preferredLocale,
        public ?string $preferredCurrency,
        public ?PersonProfileFormData $person,
        public ?CompanyProfileFormData $company,
    ) {}

    public static function fromModel(Customer $customer): self
    {
        $person = $customer->personProfile;
        $company = $customer->companyProfile;

        return new self(
            id: $customer->id,
            number: $customer->customer_number,
            name: $customer->displayName(),
            type: $customer->type->value,
            status: $customer->status->value,
            source: $customer->source->value,
            preferredLocale: $customer->preferred_locale,
            preferredCurrency: $customer->preferred_currency?->value,
            person: $person === null ? null : new PersonProfileFormData($person->salutation, $person->first_name, $person->last_name, $person->email, $person->phone),
            company: $company === null ? null : new CompanyProfileFormData($company->legal_name, $company->legal_form, $company->company_number, $company->vat_id, $company->country_code->value, $company->industry, $company->website),
        );
    }
}
