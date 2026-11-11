<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Larasell\Catalog\AttributeCardinality;
use Larasell\Larasell\Catalog\Models\ProductAttribute;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An attribute on its own page, named in the content locale. */
#[TypeScript]
class AttributeDetailData extends Data
{
    public function __construct(
        public int $id,
        /** In the content locale, null while the attribute has none in it. */
        public ?string $name,
        /** The name in another language the store shows while it has none in the content locale; null when no other language has one. */
        public ?string $fallbackName,
        public string $slug,
        #[LiteralTypeScriptType('"text" | "number" | "boolean"')]
        public string $type,
        public string $scope,
        public bool $multiple,
        /** Products with values of it. */
        public int $products,
        /** Variants with their own values of it. */
        public int $variants,
    ) {}

    /** The attribute must come with products_count and variants_count, see Attributes::withUsage(). */
    public static function fromModel(ProductAttribute $attribute, Locale $locale): self
    {
        return new self(
            id: $attribute->id,
            name: $attribute->name->has($locale->value) ? $attribute->name->get($locale->value) : null,
            fallbackName: $attribute->name->without($locale->value)?->get($locale->value),
            slug: $attribute->slug,
            type: $attribute->type->value,
            scope: $attribute->scope->value,
            multiple: $attribute->cardinality === AttributeCardinality::Multiple,
            products: (int) $attribute->getAttribute('products_count'),
            variants: (int) $attribute->getAttribute('variants_count'),
        );
    }
}
