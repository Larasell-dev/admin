<?php

namespace Larasell\Admin\Data\View;

use Larasell\Admin\Data\ViewModel\Categories\CategoryOptionData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateProductPageData extends Data
{
    public function __construct(
        public string $storeUrl,
        public string $indexUrl,
        /** @var array<int, CategoryOptionData> */
        public array $categories,
        public ?string $createCategoryUrl,
        public string $uploadImagesUrl,
    ) {}
}
