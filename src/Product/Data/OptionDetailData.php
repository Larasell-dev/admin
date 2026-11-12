<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Larasell\Catalog\Options\Models\OptionDefinition;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An option on its own page, named in the content locale. */
#[TypeScript]
class OptionDetailData extends Data
{
    public function __construct(
        public int $id,
        /** In the content locale, null while the option has none in it. */
        public ?string $name,
        /** The name in another language the store shows while it has none in the content locale; null when no other language has one. */
        public ?string $fallbackName,
        public string $slug,
        #[LiteralTypeScriptType('"dropdown" | "buttons" | "swatch"')]
        public string $display,
        /** Products that offer it. */
        public int $products,
        /** Variants with a value of it. */
        public int $variants,
    ) {}

    /** The option must come with products_count and variants_count, see Options::withUsage(). */
    public static function fromModel(OptionDefinition $option, Locale $locale): self
    {
        return new self(
            id: $option->id,
            name: $option->name->has($locale->value) ? $option->name->get($locale->value) : null,
            fallbackName: $option->name->without($locale->value)?->get($locale->value),
            slug: $option->slug,
            display: $option->display,
            products: (int) $option->getAttribute('products_count'),
            variants: (int) $option->getAttribute('variants_count'),
        );
    }
}
