<?php

namespace Larasell\Admin\Customer\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateCustomerPageData extends Data
{
    public function __construct(
        public string $storeUrl,
        public string $indexUrl,
        /** @var array<int, CustomerOptionData> */
        public array $locales,
        /** @var array<int, CustomerOptionData> */
        public array $currencies,
        /** @var array<int, CustomerOptionData> */
        public array $countries,
    ) {}
}
