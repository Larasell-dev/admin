<?php

namespace Larasell\Admin\Product\Data\View;

use Larasell\Admin\Product\Tables\Products;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProductsPageData extends Data
{
    public function __construct(
        #[LiteralTypeScriptType('import("./index").TableResource')]
        public Products $products,
    ) {}
}
