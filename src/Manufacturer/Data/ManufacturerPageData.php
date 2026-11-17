<?php

namespace Larasell\Admin\Manufacturer\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ManufacturerPageData extends Data
{
    public function __construct(
        public ManufacturerDetailData $manufacturer,
        /** @var array<int, CountryOptionData> */
        public array $countries,
        public string $updateUrl,
        public string $destroyUrl,
        public string $indexUrl,
    ) {}
}
