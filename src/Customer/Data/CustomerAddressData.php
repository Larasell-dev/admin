<?php

namespace Larasell\Admin\Customer\Data;

use Larasell\Larasell\Customers\Models\CustomerAddress;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CustomerAddressData extends Data
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public ?string $company,
        /** @var array<int, string> */
        public array $street,
        public string $postcode,
        public string $city,
        public ?string $region,
        public string $countryCode,
        public ?string $phone,
        public bool $defaultBilling,
        public bool $defaultShipping,
        public string $updateUrl,
        public string $destroyUrl,
        public string $defaultUrl,
    ) {}

    public static function fromModel(CustomerAddress $address): self
    {
        $route = [$address->customer_id, $address];

        return new self(
            id: $address->id,
            firstName: $address->first_name,
            lastName: $address->last_name,
            company: $address->company,
            street: $address->street,
            postcode: $address->postcode,
            city: $address->city,
            region: $address->region,
            countryCode: $address->country_code->value,
            phone: $address->phone?->value(),
            defaultBilling: $address->is_default_billing,
            defaultShipping: $address->is_default_shipping,
            updateUrl: route('larasell.admin.customers.addresses.update', $route),
            destroyUrl: route('larasell.admin.customers.addresses.destroy', $route),
            defaultUrl: route('larasell.admin.customers.addresses.default', $route),
        );
    }
}
