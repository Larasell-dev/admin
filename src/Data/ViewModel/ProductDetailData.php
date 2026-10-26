<?php

namespace Larasell\Admin\Data\ViewModel;

use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Product;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProductDetailData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public bool $visible,
        public ?WeightData $weight,
        public ?DimensionsData $dimensions,
        public ?string $sku,
        public ?string $barcode,
        public ?int $stock,
        public bool $allowBackorders,
        public ?int $minQuantity,
        public ?int $maxQuantity,
    ) {}

    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            name: $product->name->get(),
            slug: $product->slug->get(),
            visible: $product->status === Visibility::Visible,
            weight: $product->weight === null ? null : WeightData::fromWeight($product->weight),
            dimensions: $product->dimensions === null ? null : DimensionsData::fromDimensions($product->dimensions),
            sku: $product->sku,
            barcode: $product->barcode,
            stock: $product->stock,
            allowBackorders: $product->allow_backorders,
            minQuantity: $product->min_quantity,
            maxQuantity: $product->max_quantity,
        );
    }
}
