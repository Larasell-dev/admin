<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Admin\Category\Data\CategoryOptionData;
use Larasell\Admin\Platform\Sections\Data\SectionData;
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
        /** @var array<int, SectionData> */
        public array $sections,
        public ProductTypeData $type,
    ) {}
}
