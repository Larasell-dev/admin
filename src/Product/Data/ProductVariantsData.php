<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Catalog\Models\ProductVariant;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinition;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinitionValue;
use Larasell\Larasell\Catalog\Options\Models\ProductOption;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A product's options, its variants, and the combinations of option values it has no variant for yet. */
#[TypeScript]
class ProductVariantsData extends Data
{
    public const array PAGE_SIZES = [10, 25, 50, 100];

    public function __construct(
        /** @var array<int, ProductOptionData> */
        public array $options,
        /** @var array<int, OptionChoiceData> Definitions the product doesn't use yet */
        public array $availableOptions,
        /** @var array<int, VariantRowData> One page of the variants */
        public array $variants,
        /** The page, its size and every variant's count, from ?variants_page and ?variants_per_page. */
        public int $page,
        public int $pageSize,
        public int $total,
        /** @var array<int, CombinationData> */
        public array $missingCombinations,
        public string $createCombinationsUrl,
        public string $optionDefinitionsUrl,
    ) {}

    public static function fromProduct(Product $product, Locale $locale): self
    {
        $options = $product->options()->with(['definition.values', 'values.definitionValue'])->get();
        $pageSize = in_array(request()->integer('variants_per_page'), self::PAGE_SIZES, true) ? request()->integer('variants_per_page') : self::PAGE_SIZES[0];
        $page = $product->variants()->with(['optionValues.option.definition', 'optionValues.definitionValue', 'product'])
            ->orderBy('position')->orderBy('id')
            ->paginate($pageSize, pageName: 'variants_page');
        $variants = $page->getCollection();
        // Every variant's combination, not just this page's, to know which are missing.
        $taken = $product->variants()->with('optionValues')->get()->map(fn (ProductVariant $variant): string => $variant->optionValues->pluck('option_definition_value_id')->sort()->implode(','))->all();

        // Every combination of the offered values, the ones without a variant.
        $combinations = $options->isEmpty() ? [] : $options->reduce(
            fn (array $carry, ProductOption $option): array => collect($carry)->flatMap(
                fn (array $combination): array => $option->values->map(fn ($value): array => [...$combination, $value->definitionValue])->all(),
            )->all(),
            [[]],
        );
        $missing = collect($combinations)
            ->reject(fn (array $values): bool => in_array(collect($values)->pluck('id')->sort()->implode(','), $taken, true))
            ->take(500)
            ->map(fn (array $values): CombinationData => new CombinationData(
                array_values(array_map(fn (OptionDefinitionValue $value): int => $value->id, $values)),
                collect($values)->map(fn ($value): string => $value->name->get($locale->value))->implode(' / '),
            ))->values()->all();

        return new self(
            options: $options->map(fn (ProductOption $option): ProductOptionData => new ProductOptionData(
                definitionId: $option->definition->id,
                name: $option->definition->name->get($locale->value),
                values: $option->values->map(fn ($value): OptionValueData => OptionValueData::fromValue($value->definitionValue, $locale))->all(),
                otherValues: $option->definition->values
                    ->whereNotIn('id', $option->values->pluck('option_definition_value_id'))
                    ->map(fn ($value): OptionValueData => OptionValueData::fromValue($value, $locale))->values()->all(),
            ))->all(),
            availableOptions: OptionDefinition::query()->with('values')->whereNotIn('id', $options->pluck('option_definition_id'))->orderBy('slug')->get()
                ->map(fn (OptionDefinition $definition): OptionChoiceData => new OptionChoiceData(
                    $definition->id,
                    $definition->name->get($locale->value),
                    $definition->values->map(fn ($value): OptionValueData => OptionValueData::fromValue($value, $locale))->all(),
                ))->all(),
            variants: $variants->map(fn (ProductVariant $variant): VariantRowData => new VariantRowData(
                id: $variant->id,
                name: $variant->name($locale->value),
                sku: $variant->sku,
                visible: $variant->setRelation('product', $product)->isVisible(),
                isDefault: $variant->is_default,
                editUrl: route('larasell.admin.products.variants.edit', [$product, $variant]),
                deleteUrl: route('larasell.admin.products.variants.destroy', [$product, $variant]),
            ))->all(),
            page: $page->currentPage(),
            pageSize: $page->perPage(),
            total: $page->total(),
            missingCombinations: $missing,
            createCombinationsUrl: route('larasell.admin.products.variants.store', $product),
            optionDefinitionsUrl: route('larasell.admin.options.index'),
        );
    }
}
