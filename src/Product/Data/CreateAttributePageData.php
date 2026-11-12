<?php

namespace Larasell\Admin\Product\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateAttributePageData extends Data
{
    public function __construct(
        public string $storeUrl,
        public string $indexUrl,
        /** @var array<int, AttributeScopeOptionData> */
        public array $scopes,
    ) {}
}
