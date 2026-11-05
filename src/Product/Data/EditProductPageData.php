<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Admin\Category\Data\CategoryOptionData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class EditProductPageData extends Data
{
    public function __construct(
        public ProductDetailData $product,
        public string $updateUrl,
        public string $indexUrl,
        /** @var array<int, CategoryOptionData> */
        public array $categories,
        public ?string $createCategoryUrl,
        public string $uploadImagesUrl,
    ) {}
}
