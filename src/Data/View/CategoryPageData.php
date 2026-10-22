<?php

namespace Larasell\Admin\Data\View;

use Larasell\Admin\Data\ViewModel\CategoryData;
use Larasell\Admin\Data\ViewModel\CategoryDetailData;
use Larasell\Admin\Tables\Panel\CategoryProducts;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CategoryPageData extends Data
{
    public function __construct(
        /** @var array<int, CategoryData> */
        public array $categories,
        public string $createUrl,
        public CategoryDetailData $category,
        public string $updateUrl,
        #[LiteralTypeScriptType('import("./index").TableResource')]
        public CategoryProducts $products,
        public string $availableProductsUrl,
        public string $addProductUrl,
    ) {}
}
