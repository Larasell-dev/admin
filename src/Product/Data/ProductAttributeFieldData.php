<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Larasell\Catalog\AttributeCardinality;
use Larasell\Larasell\Catalog\AttributeScope;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Catalog\Models\ProductAttribute;
use Larasell\Larasell\Catalog\Models\ProductAttributeValue;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An attribute the product form sets a value of: one only the product has, or one its variants inherit. */
#[TypeScript]
class ProductAttributeFieldData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        /** Whether the product's variants take the value unless they set their own. */
        public bool $inherited,
        /** Whether it takes several values. */
        public bool $multiple,
        /** @var list<int> The product's values' IDs. */
        #[LiteralTypeScriptType('number[]')]
        public array $value,
        /** @var array<int, array{id: int, name: string}> */
        #[LiteralTypeScriptType('Array<{ id: number; name: string }>')]
        public array $values,
    ) {}

    /**
     * The attributes the product form shows, with the product's values; none set for a new product.
     *
     * @return array<int, self>
     */
    public static function forProduct(?Product $product, Locale $locale): array
    {
        $values = $product?->attributeValues()->get()->groupBy('product_attribute_id');

        return ProductAttribute::query()->with('values')
            ->whereIn('scope', [AttributeScope::Product->value, AttributeScope::Inherited->value])
            ->has('values')
            ->orderBy('slug')->get()
            ->map(fn (ProductAttribute $attribute): self => new self(
                id: $attribute->id,
                name: $attribute->name->get($locale->value),
                inherited: $attribute->scope === AttributeScope::Inherited,
                multiple: $attribute->cardinality === AttributeCardinality::Multiple,
                value: array_values(array_map(intval(...), $values?->get($attribute->id)?->modelKeys() ?? [])),
                values: $attribute->values->map(fn (ProductAttributeValue $value): array => ['id' => $value->id, 'name' => $value->name->get($locale->value)])->all(),
            ))->all();
    }
}
