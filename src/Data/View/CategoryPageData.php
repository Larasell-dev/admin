<?php

namespace Larasell\Admin\Data\View;

use Larasell\Admin\Data\ViewModel\CategoryData;
use Larasell\Admin\Data\ViewModel\CategoryDetailData;
use Larasell\Admin\Tables\Panel\CategoryProducts;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Everything about the category is null when it does not exist (anymore).
 */
#[TypeScript]
class CategoryPageData extends Data
{
    public function __construct(
        /** @var array<int, CategoryData> */
        public array $categories,
        public string $createUrl,
        public ?CategoryDetailData $category = null,
        public ?string $updateUrl = null,
        #[LiteralTypeScriptType('import("./index").TableResource | null')]
        public ?CategoryProducts $products = null,
        public ?string $availableProductsUrl = null,
        public ?string $addProductUrl = null,
    ) {}
}
