<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Spatie\LaravelData\Data;
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
        public bool $visible,
        public ?WeightData $weight,
        public ?DimensionsData $dimensions,
        public ?string $sku,
        public ?string $barcode,
        public ?int $stock,
        public bool $allowBackorders,
        public ?int $minQuantity,
        public ?int $maxQuantity,
        /** @var int[] */
        public array $categoryIds,
        /** @var ProductImageData[] In the product's order, so the first is its thumbnail. */
        public array $images,
    ) {}

    public static function fromModel(Product $product, Locale $locale): self
    {
        // Qualified, as the pivot table has an id too.
        $categoryKey = $product->categories()->getRelated()->getQualifiedKeyName();

        return new self(
            id: $product->id,
            name: $product->name->has($locale->value) ? $product->name->get($locale->value) : null,
            fallbackName: $product->name->get($locale->value),
            visible: $product->status === Visibility::Visible,
            weight: $product->weight === null ? null : WeightData::fromWeight($product->weight),
            dimensions: $product->dimensions === null ? null : DimensionsData::fromDimensions($product->dimensions),
            sku: $product->sku,
            barcode: $product->barcode,
            stock: $product->stock,
            allowBackorders: $product->allow_backorders,
            minQuantity: $product->min_quantity,
            maxQuantity: $product->max_quantity,
            categoryIds: $product->categories()->orderBy($categoryKey)->pluck($categoryKey)->all(),
            images: $product->images->map(ProductImageData::fromModel(...))->all(),
        );
    }
}
