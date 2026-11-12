<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Admin\Category\Data\CategoryOptionData;
use Larasell\Admin\Platform\Sections\Data\SectionData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
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
        /** @var array<int, array{code: string, label: string}> */
        #[LiteralTypeScriptType('Array<{ code: string; label: string }>')]
        public array $countries,
        /** @var array<int, ProductAttributeFieldData> */
        public array $attributes,
        /** The catalog's attributes, where their values are defined. */
        public string $attributesUrl,
    ) {}
}
