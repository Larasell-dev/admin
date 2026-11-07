<?php

namespace Larasell\Admin\Seo\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The URLs section of the product and category pages: one URL per storefront channel that sells in the
 * content locale.
 */
#[TypeScript]
class SeoUrlsData extends Data
{
    public function __construct(
        /** @var array<int, SeoUrlData> */
        public array $urls,
    ) {}
}
