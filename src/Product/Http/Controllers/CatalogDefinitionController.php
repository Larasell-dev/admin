<?php

namespace Larasell\Admin\Product\Http\Controllers;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Larasell\Catalog\AttributeCardinality;
use Larasell\Larasell\Catalog\AttributeScope;
use Larasell\Larasell\Catalog\Models\ProductAttribute;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinition;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\ProductAttributeType;

/** The global option and attribute definitions products pick from. */
class CatalogDefinitionController
{
    public function options(#[CurrentContentLocale] Locale $locale): Response
    {
        $l = $locale->value;

        return Inertia::render('Product/Pages/Options/Index', [
            'definitions' => OptionDefinition::query()->with('values')->withCount(['values'])->orderBy('slug')->get()->map(fn (OptionDefinition $d): array => [
                'id' => $d->id, 'slug' => $d->slug, 'name' => $d->name->get($l), 'display' => $d->display,
                'products' => \DB::table('larasell_product_options')->where('option_definition_id', $d->id)->count(),
                'values' => $d->values->map(fn ($v): array => ['id' => $v->id, 'name' => $v->name->get($l), 'swatch' => $v->swatch])->all(),
                'addValueUrl' => route('larasell.admin.options.values.store', $d),
            ])->all(),
            'storeUrl' => route('larasell.admin.options.store'),
        ]);
    }

    public function storeOption(Request $request, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'alpha_dash', 'max:64', Rule::unique(OptionDefinition::class)],
            'name' => ['required', 'string', 'max:255'],
            'display' => ['required', Rule::in(['dropdown', 'buttons', 'swatch'])],
        ]);
        OptionDefinition::query()->create([...$data, 'name' => [$locale->value => $data['name']]]);

        return back();
    }

    public function storeOptionValue(Request $request, OptionDefinition $definition, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'swatch' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $definition->values()->create([
            'slug' => str($data['name'])->slug()->value() ?: 'value-'.($definition->values()->count() + 1),
            'name' => [$locale->value => $data['name']],
            'swatch' => $data['swatch'] ?? null,
            'position' => (int) $definition->values()->max('position') + 1,
        ]);

        return back();
    }

    public function attributes(#[CurrentContentLocale] Locale $locale): Response
    {
        $l = $locale->value;

        return Inertia::render('Product/Pages/Attributes/Index', [
            'attributes' => ProductAttribute::query()->with('values')->orderBy('slug')->get()->map(fn (ProductAttribute $a): array => [
                'id' => $a->id, 'slug' => $a->slug, 'name' => $a->name->get($l), 'type' => $a->type->value, 'scope' => $a->scope->value, 'cardinality' => $a->cardinality->value,
                'values' => $a->values->map(fn ($v): array => ['id' => $v->id, 'name' => $v->name->get($l)])->all(),
                'updateUrl' => route('larasell.admin.attributes.update', $a),
                'addValueUrl' => route('larasell.admin.attributes.values.store', $a),
            ])->all(),
            'storeUrl' => route('larasell.admin.attributes.store'),
        ]);
    }

    public function storeAttribute(Request $request, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'alpha_dash', 'max:64', Rule::unique(ProductAttribute::class)],
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', Rule::enum(AttributeScope::class)],
            'cardinality' => ['sometimes', Rule::enum(AttributeCardinality::class)],
        ]);
        ProductAttribute::query()->create([...$data, 'name' => [$locale->value => $data['name']], 'type' => ProductAttributeType::Text]);

        return back();
    }

    public function updateAttribute(Request $request, ProductAttribute $attribute): RedirectResponse
    {
        $attribute->update($request->validate([
            'scope' => ['sometimes', Rule::enum(AttributeScope::class)],
            'cardinality' => ['sometimes', Rule::enum(AttributeCardinality::class), function (string $field, mixed $value, Closure $fail) use ($attribute): void {
                // Back to one value only once no product or variant has several.
                if ($value === AttributeCardinality::Single->value && $this->hasSeveralValues($attribute)) {
                    $fail(__('Some products or variants have several values of :attribute. Leave them one each first.', ['attribute' => $attribute->name->get(app()->getLocale())]));
                }
            }],
        ]));

        return back();
    }

    public function storeAttributeValue(Request $request, ProductAttribute $attribute, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $attribute->values()->create([
            'slug' => str($data['name'])->slug()->value(),
            'name' => [$locale->value => $data['name']],
            'value' => $data['name'],
        ]);

        return back();
    }

    private function hasSeveralValues(ProductAttribute $attribute): bool
    {
        $several = fn (string $table, string $owner, string $value) => DB::table($table)
            ->join('larasell_product_attribute_values', 'larasell_product_attribute_values.id', '=', "{$table}.{$value}")
            ->where('larasell_product_attribute_values.product_attribute_id', $attribute->id)
            ->groupBy("{$table}.{$owner}")->havingRaw('count(*) > 1')->exists();

        return $several('larasell_product_product_attribute_value', 'product_id', 'product_attribute_value_id')
            || $several('larasell_product_variant_attribute_values', 'product_variant_id', 'product_attribute_value_id');
    }
}
