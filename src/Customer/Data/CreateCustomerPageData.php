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
    ) {}
}
