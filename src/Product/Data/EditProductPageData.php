<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Admin\Category\Data\CategoryOptionData;
use Larasell\Admin\Platform\Sections\Data\SectionData;
use Larasell\Larasell\Enums\Country;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
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

    /**
     * Every country, named in the interface language.
     *
     * @return array<int, array{code: string, label: string}>
     */
    public static function countries(): array
    {
        return array_map(fn (Country $country): array => ['code' => $country->value, 'label' => \Locale::getDisplayRegion("-{$country->value}", app()->getLocale()) ?: $country->value], Country::cases());
    }
}
