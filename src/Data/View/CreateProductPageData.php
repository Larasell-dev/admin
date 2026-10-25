<?php

namespace Larasell\Admin\Data\View;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateProductPageData extends Data
{
    public function __construct(
        public string $storeUrl,
        public string $indexUrl,
    ) {}
}
