<?php

namespace Larasell\Admin\Brand\Data;

use Larasell\Larasell\Catalog\Brands\Models\Brand;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A brand on its own page, its texts in the content locale. */
#[TypeScript]
class BrandDetailData extends Data
{
    public function __construct(
        public int $id,
        /** In the content locale, null while the brand has none in it. */
        public ?string $name,
        /** The name in another language the store shows while it has none in the content locale. */
        public ?string $fallbackName,
        public string $slug,
        public ?string $description,
        /** Products of the brand. */
        public int $products,
    ) {}

    /** The brand must come with products_count. */
    public static function fromModel(Brand $brand, Locale $locale): self
    {
        return new self(
            id: $brand->id,
            name: $brand->name->has($locale->value) ? $brand->name->get($locale->value) : null,
            fallbackName: $brand->name->without($locale->value)?->get($locale->value),
            slug: $brand->slug,
            description: $brand->description?->has($locale->value) ? $brand->description->get($locale->value) : null,
            products: (int) $brand->getAttribute('products_count'),
        );
    }
}
