<?php

namespace Larasell\Admin\Brand\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class BrandPageData extends Data
{
    public function __construct(
        public BrandDetailData $brand,
        public string $updateUrl,
        public string $destroyUrl,
        public string $indexUrl,
    ) {}
}
