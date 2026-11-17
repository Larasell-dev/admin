<?php

namespace Larasell\Admin\Manufacturer\Data;

use Larasell\Larasell\Enums\Country;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A country to pick for an address, named in the interface language. */
#[TypeScript]
class CountryOptionData extends Data
{
    public function __construct(
        public string $code,
        public string $label,
    ) {}

    /** @return list<self> */
    public static function options(): array
    {
        return array_map(fn (Country $country): self => new self($country->value, $country->label(app()->getLocale())), Country::cases());
    }
}
