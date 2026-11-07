<?php

namespace Larasell\Admin\Seo\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The URLs section of the product page: one URL per storefront channel that sells in the
 * content locale.
 */
#[TypeScript]
class ProductUrlsData extends Data
{
    public function __construct(
        /** @var array<int, ProductUrlData> */
        public array $urls,
    ) {}
}
