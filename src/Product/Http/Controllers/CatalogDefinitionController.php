<?php

namespace Larasell\Admin\Product\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinition;
use Larasell\Larasell\Enums\Locale;

/** The global option definitions products pick from. */
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
}
