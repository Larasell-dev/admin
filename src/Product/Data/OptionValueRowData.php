<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Larasell\Catalog\Options\Models\OptionDefinitionValue;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One of an option's values on the option's page, named in the content locale. */
#[TypeScript]
class OptionValueRowData extends Data
{
    public function __construct(
        public int $id,
        /** In the content locale, null while the value has none in it. */
        public ?string $name,
        /** The name in another language the store shows while it has none in the content locale; null when no other language has one. */
        public ?string $fallbackName,
        public string $slug,
        /** A colour such as #000000, or null. */
        public ?string $swatch,
        /** Products whose variants use it. */
        public int $products,
        /** Variants that use it; while any do, it can't be deleted. */
        public int $variants,
    ) {}

    /** The value must come with products_count and variants_count, see OptionController::show(). */
    public static function fromModel(OptionDefinitionValue $value, Locale $locale): self
    {
        return new self(
            id: $value->id,
            name: $value->name->has($locale->value) ? $value->name->get($locale->value) : null,
            fallbackName: $value->name->without($locale->value)?->get($locale->value),
            slug: $value->slug,
            swatch: $value->swatch,
            products: (int) $value->getAttribute('products_count'),
            variants: (int) $value->getAttribute('variants_count'),
        );
    }
}
