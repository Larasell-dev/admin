<?php

namespace Larasell\Admin\Manufacturer\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateManufacturerPageData extends Data
{
    public function __construct(
        /** @var array<int, CountryOptionData> */
        public array $countries,
        public string $storeUrl,
        public string $indexUrl,
    ) {}
}
