<?php

namespace Larasell\Admin\Manufacturer\Data;

use Larasell\Larasell\Catalog\Manufacturers\PostalAddress;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A manufacturer's or responsible person's postal address, as the form edits it. */
#[TypeScript]
class PostalAddressData extends Data
{
    public function __construct(
        /** The street lines, one per line. */
        public string $street,
        public string $postcode,
        public string $city,
        public ?string $state,
        /** ISO 3166-1 alpha-2, e.g. DE. */
        public string $country,
    ) {}

    public static function fromAddress(PostalAddress $address): self
    {
        return new self(implode("\n", $address->street), $address->postcode, $address->city, $address->state, $address->country->value);
    }
}
