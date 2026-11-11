<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Larasell\Catalog\Models\ProductAttributeValue;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One of an attribute's values on the attribute's page, named in the content locale. */
#[TypeScript]
class AttributeValueRowData extends Data
{
    public function __construct(
        public int $id,
        /** In the content locale, null while the value has none in it. */
        public ?string $name,
        /** The name in another language the store shows while it has none in the content locale; null when no other language has one. */
        public ?string $fallbackName,
        public string $slug,
        /** Products that have it. */
        public int $products,
        /** Variants that have it as their own value. */
        public int $variants,
    ) {}

    /** The value must come with products_count and variants_count, see AttributeController::show(). */
    public static function fromModel(ProductAttributeValue $value, Locale $locale): self
    {
        return new self(
            id: $value->id,
            name: $value->name->has($locale->value) ? $value->name->get($locale->value) : null,
            fallbackName: $value->name->without($locale->value)?->get($locale->value),
            slug: $value->slug,
            products: (int) $value->getAttribute('products_count'),
            variants: (int) $value->getAttribute('variants_count'),
        );
    }
}
