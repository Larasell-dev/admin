<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProductDetailData extends Data
{
    public function __construct(
        public int $id,
        /** In the content locale, null while the product has none in it. */
        public ?string $name,
        /** What the store shows in the content locale, the name of another locale while it has none. */
        public string $fallbackName,
        /**
         * In the content locale, null while the product has none in it.
         *
         * @var array<int, array<string, mixed>>|null
         */
        #[LiteralTypeScriptType('import("../Platform/RichText/types").RichText | null')]
        public ?array $description,
        public bool $visible,
        public ?WeightData $weight,
        public ?DimensionsData $dimensions,
        public ?string $sku,
        public ?string $barcode,
        /** Manufacturer part number. Identifiers are the default variant's. */
        public ?string $mpn,
        public ?string $countryOfOrigin,
        /** The customs tariff number. */
        public ?string $hsCode,
        public ?int $stock,
        public bool $allowBackorders,
        public ?int $minQuantity,
        public ?int $maxQuantity,
        /** @var int[] */
        public array $categoryIds,
    ) {}

    public static function fromModel(Product $product, Locale $locale): self
    {
        // Qualified, as the pivot table has an id too.
        $categoryKey = $product->categories()->getRelated()->getQualifiedKeyName();

        return new self(
            id: $product->id,
            name: $product->name->has($locale->value) ? $product->name->get($locale->value) : null,
            fallbackName: $product->name->get($locale->value),
            description: $product->description?->has($locale->value)
                ? $product->description->get($locale->value)->toArray()
                : null,
            visible: $product->status === Visibility::Visible,
            weight: $product->weight === null ? null : WeightData::fromWeight($product->weight),
            dimensions: $product->dimensions === null ? null : DimensionsData::fromDimensions($product->dimensions),
            sku: $product->defaultVariant()->sku,
            barcode: $product->defaultVariant()->barcode,
            mpn: $product->defaultVariant()->mpn,
            countryOfOrigin: $product->country_of_origin?->value,
            hsCode: $product->hs_code,
            stock: $product->stock,
            allowBackorders: $product->allow_backorders,
            minQuantity: $product->min_quantity,
            maxQuantity: $product->max_quantity,
            categoryIds: $product->categories()->orderBy($categoryKey)->pluck($categoryKey)->all(),
        );
    }
}
