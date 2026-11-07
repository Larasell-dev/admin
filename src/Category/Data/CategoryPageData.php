<?php

namespace Larasell\Admin\Category\Data;

use Larasell\Admin\Category\Tables\CategoryProducts;
use Larasell\Admin\Platform\Sections\Data\SectionData;
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
        /** Null once the store has products to add instead, or while the products are turned off. */
        public ?string $createProductUrl,
        /** @var array<int, SectionData> */
        public array $sections,
    ) {}
}
