<?php

namespace Larasell\Admin\Data\View;

use Larasell\Admin\Data\ViewModel\ProductDetailData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class EditProductPageData extends Data
{
    public function __construct(
        public ProductDetailData $product,
        public string $updateUrl,
        public string $indexUrl,
    ) {}
}
