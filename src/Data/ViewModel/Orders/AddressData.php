<?php

namespace Larasell\Admin\Data\ViewModel\Orders;

use Larasell\Larasell\Address;
use Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An address of the order, by its parts, with the country spelled out. */
#[TypeScript]
class AddressData extends Data
{
    public function __construct(
        public string $name,
        public ?string $company,
        /** @var string[] */
        public array $street,
        public string $postcode,
        public string $city,
        public ?string $state,
        public string $country,
    ) {}

    public static function fromAddress(Address $address): self
    {
        return new self(
            name: trim("{$address->firstName} {$address->lastName}"),
            company: $address->company,
            street: $address->street,
            postcode: $address->postcode,
            city: $address->city,
            state: $address->state,
            country: Locale::getDisplayRegion("-{$address->country}", app()->getLocale()) ?: $address->country,
        );
    }
}
