<?php

namespace Larasell\Admin\Product\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Product\Data\EditVariantPageData;
use Larasell\Admin\Product\Rules\Barcode;
use Larasell\Larasell\Barcode as LarasellBarcode;
use Larasell\Larasell\Catalog\Actions\DeleteProductVariant;
use Larasell\Larasell\Catalog\Actions\UpdateProductVariant;
use Larasell\Larasell\Catalog\AttributeScope;
use Larasell\Larasell\Catalog\Events\ProductVariantCreated;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Catalog\Models\ProductAttribute;
use Larasell\Larasell\Catalog\Models\ProductAttributeValue;
use Larasell\Larasell\Catalog\Models\ProductVariant;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinition;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinitionValue;
use Larasell\Larasell\Catalog\ProductId;
use Larasell\Larasell\Catalog\ProductTypes;
use Larasell\Larasell\Catalog\VariantId;
use Larasell\Larasell\Dimensions;
use Larasell\Larasell\Enums\Country;
use Larasell\Larasell\Enums\LengthUnit;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Enums\WeightUnit;
use Larasell\Larasell\RichText\RichText;
use Larasell\Larasell\Weight;
use Laravel\Head\Facades\Head;

/** A product's options, the variants they tell apart, and each variant's own fields. */
class ProductVariantController
{
    private const array STATES = ['inherited', 'overridden'];

    /** Uses an option definition on the product, offering the picked values. */
    public function storeOption(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'definition' => ['required', 'integer', Rule::exists(OptionDefinition::class, 'id')],
            'values' => ['required', 'array', 'min:1'],
            'values.*' => ['integer', 'distinct'],
        ]);
        $definition = OptionDefinition::query()->findOrFail($data['definition']);
        $values = $definition->values()->whereKey($data['values'])->get();

        if ($values->count() !== count($data['values'])) {
            return back()->withErrors(['values' => __('Pick values of the chosen option.')]);
        }

        DB::transaction(fn () => $product->addOption($definition, $values->all()));

        return back();
    }

    public function storeOptionValue(Request $request, Product $product, OptionDefinition $definition): RedirectResponse
    {
        $data = $request->validate(['value' => ['required', 'integer', Rule::exists(OptionDefinitionValue::class, 'id')->where('option_definition_id', $definition->id)]]);

        $product->addOptionValue($definition, OptionDefinitionValue::query()->findOrFail($data['value']));

        return back();
    }

    /** Creates the variants of the picked combinations. */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'combinations' => ['required', 'array', 'min:1'],
            'combinations.*' => ['required', 'array'],
            'combinations.*.*' => ['integer'],
        ]);

        DB::transaction(function () use ($product, $data): void {
            $values = OptionDefinitionValue::query()->whereKey(collect($data['combinations'])->flatten()->all())->get()->keyBy('id');
            $defaultId = $product->defaultVariant()->id;
            $variants = $product->createCombinations(array_map(
                fn (array $ids): array => array_map(fn (int $id): OptionDefinitionValue => $values->get($id) ?? abort(422), $ids),
                $data['combinations'],
            ));

            foreach ($variants as $variant) {
                if ($variant->id !== $defaultId) {
                    ProductVariantCreated::dispatch(VariantId::of($variant), ProductId::of($product));
                }
            }
        });

        return back();
    }

    public function edit(Product $product, ProductVariant $variant, ProductTypes $types, #[CurrentContentLocale] Locale $locale): Response
    {
        abort_unless($variant->product_id === $product->id, 404);
        Head::title($variant->name($locale->value));

        return Inertia::render('Product/Pages/Variants/Edit', EditVariantPageData::fromVariant($variant, $types, $locale));
    }

    public function update(Request $request, Product $product, ProductVariant $variant, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        $barcode = $request->input('barcode');

        try {
            if (is_string($barcode) && $barcode !== '') {
                $request->merge(['barcode' => LarasellBarcode::of($barcode)->value()]);
            }
        } catch (\InvalidArgumentException) {
            // The Barcode rule reports it.
        }

        $state = ['required', Rule::in(self::STATES)];
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:255', Rule::unique(ProductVariant::class)->ignore($variant)],
            'barcode' => ['nullable', 'string', new Barcode, Rule::unique(ProductVariant::class)->ignore($variant)],
            'mpn' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(Visibility::class)],
            'weight.state' => $state,
            'weight.amount' => ['exclude_unless:weight.state,overridden', 'nullable', 'regex:/^\d+(\.\d+)?$/'],
            'weight.unit' => ['exclude_unless:weight.state,overridden', 'nullable', Rule::enum(WeightUnit::class)],
            'dimensions.state' => $state,
            'dimensions.length' => ['exclude_unless:dimensions.state,overridden', 'nullable', 'regex:/^\d+(\.\d+)?$/'],
            'dimensions.width' => ['exclude_unless:dimensions.state,overridden', 'nullable', 'regex:/^\d+(\.\d+)?$/'],
            'dimensions.height' => ['exclude_unless:dimensions.state,overridden', 'nullable', 'regex:/^\d+(\.\d+)?$/'],
            'dimensions.unit' => ['exclude_unless:dimensions.state,overridden', 'nullable', Rule::enum(LengthUnit::class)],
            'country_of_origin.state' => $state,
            'country_of_origin.value' => ['exclude_unless:country_of_origin.state,overridden', 'nullable', Rule::enum(Country::class)],
            'hs_code.state' => $state,
            'hs_code.value' => ['exclude_unless:hs_code.state,overridden', 'nullable', 'string', 'max:16'],
            'name.state' => ['required', Rule::in(['inherited', 'overridden'])],
            'name.value' => ['exclude_unless:name.state,overridden', 'required', 'string', 'max:255'],
            'description.state' => $state,
            'description.value' => ['exclude_unless:description.state,overridden', 'nullable', 'string'],
            'attributes' => ['sometimes', 'array'],
            'attributes.*.state' => $state,
            // One value ID, or a list of them for multi-value attributes; empty for none.
            'attributes.*.value' => ['exclude_unless:attributes.*.state,overridden', 'nullable'],
            'attributes.*.value.*' => ['exclude_unless:attributes.*.state,overridden', 'integer'],
        ]);

        DB::transaction(function () use ($variant, $data, $locale): void {
            $variant = ProductVariant::query()->findOrFail($variant->id);
            $values = [
                'weight' => fn (array $field): ?Weight => ($field['amount'] ?? null) === null ? null : Weight::of($field['amount'], $field['unit'] ?? 'g'),
                'dimensions' => fn (array $field): ?Dimensions => ($field['length'] ?? null) === null ? null : Dimensions::of($field['length'], $field['width'], $field['height'], $field['unit'] ?? 'cm'),
                'country_of_origin' => fn (array $field): ?Country => ($field['value'] ?? null) === null ? null : Country::from($field['value']),
                'hs_code' => fn (array $field): ?string => $field['value'] ?? null,
            ];
            $changes = ['sku' => $data['sku'] ?? null, 'barcode' => $data['barcode'] ?? null, 'mpn' => $data['mpn'] ?? null, 'status' => $data['status']];

            foreach ($values as $field => $value) {
                $state = $data[$field]['state'];
                $changes["{$field}_inheritance"] = $state;
                $changes[$field] = $state === 'overridden' ? $value($data[$field]) : null;
            }

            foreach (['name', 'description'] as $field) {
                $copy = clone $variant;
                match ($data[$field]['state']) {
                    'inherited' => $copy->inheritTranslation($field, $locale->value),
                    default => $copy->overrideTranslation($field, $locale->value, $field === 'description'
                        ? (($data[$field]['value'] ?? null) === null || RichText::fromJson($data[$field]['value'])->isEmpty() ? null : RichText::fromJson($data[$field]['value']))
                        : $data[$field]['value']),
                };
                $changes[$field] = $copy->{$field};
            }

            app(UpdateProductVariant::class)->handle(VariantId::of($variant), $changes);

            foreach ($data['attributes'] ?? [] as $attributeId => $field) {
                $attribute = ProductAttribute::query()->findOrFail((int) $attributeId);

                if ($attribute->scope === AttributeScope::Product) {
                    continue;
                }

                match ($field['state']) {
                    'inherited' => $variant->inheritAttribute($attribute),
                    default => $variant->overrideAttribute($attribute, ...ProductAttributeValue::query()
                        ->where('product_attribute_id', $attribute->id)
                        ->findOrFail(array_map(intval(...), array_filter(Arr::wrap($field['value'] ?? null), fn (mixed $id): bool => $id !== null && $id !== '')))
                        ->all()),
                };
            }
        });

        return back();
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_unless($variant->product_id === $product->id, 404);

        if ($variant->is_default || $product->variants()->count() === 1) {
            return back()->withErrors(['variant' => __('A product keeps its default variant.')]);
        }

        app(DeleteProductVariant::class)->handle(VariantId::of($variant));

        return redirect()->route('larasell.admin.products.edit', $product);
    }
}
