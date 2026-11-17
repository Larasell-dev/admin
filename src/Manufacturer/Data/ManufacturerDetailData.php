<?php

namespace Larasell\Admin\Manufacturer\Data;

use Larasell\Larasell\Catalog\Manufacturers\Models\Manufacturer;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A manufacturer on its own page. */
#[TypeScript]
class ManufacturerDetailData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public PostalAddressData $address,
        public ?string $email,
        public ?string $url,
        public ?string $responsiblePersonName,
        public ?string $responsiblePersonEmail,
        public ?PostalAddressData $responsiblePersonAddress,
        /** Whether its address is in the EU, so it needs no responsible person. */
        public bool $inEu,
        /** Products it makes. */
        public int $products,
    ) {}

    /** The manufacturer must come with products_count. */
    public static function fromModel(Manufacturer $manufacturer): self
    {
        $person = $manufacturer->responsible_person;

        return new self(
            id: $manufacturer->id,
            name: $manufacturer->name,
            address: PostalAddressData::fromAddress($manufacturer->address),
            email: $manufacturer->email,
            url: $manufacturer->url,
            responsiblePersonName: $person?->name,
            responsiblePersonEmail: $person?->email,
            responsiblePersonAddress: $person ? PostalAddressData::fromAddress($person->address) : null,
            inEu: $manufacturer->isInEu(),
            products: (int) $manufacturer->getAttribute('products_count'),
        );
    }
}
