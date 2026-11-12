<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Larasell\Catalog\AttributeCardinality;
use Larasell\Larasell\Catalog\AttributeScope;
use Larasell\Larasell\Catalog\Models\ProductAttribute;
use Larasell\Larasell\Catalog\Models\ProductAttributeValue;
use Larasell\Larasell\Catalog\Models\ProductVariant;
use Larasell\Larasell\Catalog\ProductTypes;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\RichText\RichText;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A variant's own fields, and for each field it can inherit: its state, its own value and the
 * product's, which the page shows while it inherits. Name and description are in the content locale.
 */
#[TypeScript]
class EditVariantPageData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $productName,
        /** @var array<int, array{option: string, value: string}> */
        #[LiteralTypeScriptType('Array<{ option: string; value: string }>')]
        public array $options,
        public bool $isDefault,
        public bool $visible,
        public ?string $sku,
        public ?string $barcode,
        public ?string $mpn,
        /** @var array<int, array{code: string, label: string}> */
        #[LiteralTypeScriptType('Array<{ code: string; label: string }>')]
        public array $countries,
        /**
         * Each inheritable field: state, own value and the product's, values as the form submits them.
         *
         * @var array<string, array{state: string, value: mixed, inherited: mixed}>
         */
        #[LiteralTypeScriptType('Record<"weight" | "dimensions" | "country_of_origin" | "hs_code" | "name" | "description", { state: "inherited" | "overridden"; value: any; inherited: any }>')]
        public array $fields,
        /** @var array<int, VariantAttributeData> */
        public array $attributes,
        /** @var array<int, array{id: int, name: string, url: string}> */
        #[LiteralTypeScriptType('Array<{ id: number; name: string; url: string }>')]
        public array $siblings,
        public string $productUrl,
        public string $updateUrl,
        public ?string $deleteUrl,
    ) {}

    public static function fromVariant(ProductVariant $variant, ProductTypes $types, Locale $locale): self
    {
        $product = $variant->product;
        $l = $locale->value;
        $state = fn (string $field): string => $variant->inheritance($field)->value;
        $text = fn (string $field): string => $variant->translationInheritance($field, $l)->value;
        $ownName = $variant->name[$l] ?? null;
        $ownDescription = $variant->description[$l] ?? null;
        $productDescription = $product->description?->has($l) ? $product->description->get($l)->toArray() : null;
        $attributeValues = $product->attributeValues()->get()->groupBy('product_attribute_id');

        return new self(
            id: $variant->id,
            title: $variant->name($l),
            productName: $product->name->get($l),
            options: collect($variant->options($l))->map(fn (array $option): array => ['option' => $option['attribute_name'], 'value' => $option['value_name']])->values()->all(),
            isDefault: $variant->is_default,
            visible: $variant->status === Visibility::Visible,
            sku: $variant->sku,
            barcode: $variant->barcode,
            mpn: $variant->mpn,
            countries: EditProductPageData::countries(),
            fields: [
                'weight' => ['state' => $state('weight'), 'value' => $variant->weight === null ? null : WeightData::fromWeight($variant->weight), 'inherited' => $product->weight === null ? null : WeightData::fromWeight($product->weight)],
                'dimensions' => ['state' => $state('dimensions'), 'value' => $variant->dimensions === null ? null : DimensionsData::fromDimensions($variant->dimensions), 'inherited' => $product->dimensions === null ? null : DimensionsData::fromDimensions($product->dimensions)],
                'country_of_origin' => ['state' => $state('country_of_origin'), 'value' => $variant->country_of_origin?->value, 'inherited' => $product->country_of_origin?->value],
                'hs_code' => ['state' => $state('hs_code'), 'value' => $variant->hs_code, 'inherited' => $product->hs_code],
                'name' => ['state' => $text('name'), 'value' => $ownName, 'inherited' => $product->name->get($l)],
                'description' => ['state' => $text('description'), 'value' => $ownDescription === null ? null : RichText::fromJson($ownDescription)->toArray(), 'inherited' => $productDescription],
            ],
            attributes: ProductAttribute::query()->with('values')->whereIn('scope', [AttributeScope::Variant->value, AttributeScope::Inherited->value])->orderBy('slug')->get()
                ->map(function (ProductAttribute $attribute) use ($variant, $attributeValues, $l): VariantAttributeData {
                    $own = $variant->effectiveAttributeValues()->filter(fn (ProductAttributeValue $value): bool => $value->product_attribute_id === $attribute->id);
                    $inherited = $attribute->scope === AttributeScope::Inherited ? $attributeValues->get($attribute->id) : null;
                    $state = $variant->attributeInheritance($attribute)->value;

                    return new VariantAttributeData(
                        id: $attribute->id,
                        name: $attribute->name->get($l),
                        inheritable: $attribute->scope === AttributeScope::Inherited,
                        multiple: $attribute->cardinality === AttributeCardinality::Multiple,
                        state: $state,
                        value: $state === 'overridden' ? array_values(array_map(intval(...), $own->modelKeys())) : [],
                        inherited: array_values(array_map(intval(...), $inherited?->modelKeys() ?? [])),
                        values: $attribute->values->map(fn (ProductAttributeValue $value): array => ['id' => $value->id, 'name' => $value->name->get($l)])->all(),
                    );
                })->all(),
            siblings: $product->variants()->orderBy('position')->orderBy('id')->get()
                ->map(fn (ProductVariant $sibling): array => ['id' => $sibling->id, 'name' => $sibling->name($l), 'url' => route('larasell.admin.products.variants.edit', [$product, $sibling])])->all(),
            productUrl: route('larasell.admin.products.edit', $product),
            updateUrl: route('larasell.admin.products.variants.update', [$product, $variant]),
            deleteUrl: $variant->is_default ? null : route('larasell.admin.products.variants.destroy', [$product, $variant]),
        );
    }
}
