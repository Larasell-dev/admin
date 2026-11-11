<?php

namespace Larasell\Admin\Product\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;
use Larasell\Admin\Product\Data\CreateOptionPageData;
use Larasell\Admin\Product\Data\OptionDetailData;
use Larasell\Admin\Product\Data\OptionDisplayOptionData;
use Larasell\Admin\Product\Data\OptionPageData;
use Larasell\Admin\Product\Data\OptionValueRowData;
use Larasell\Admin\Product\Http\Requests\OptionRequest;
use Larasell\Admin\Product\Tables\Options;
use Larasell\Larasell\Catalog\Options\Actions\DeleteOptionDefinition;
use Larasell\Larasell\Catalog\Options\Actions\SaveOptionDefinition;
use Larasell\Larasell\Catalog\Options\Exceptions\OptionInUse;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinition;
use Larasell\Larasell\Catalog\Options\OptionDefinitionId;
use Larasell\Larasell\Enums\Locale;
use Laravel\Head\Facades\Head;

/**
 * The catalog's options, such as Colour or Size, whose values tell a product's variants apart.
 * Options and values variants use can't be deleted here: their variants go first, on the product.
 */
class OptionController
{
    public function create(): Response
    {
        return Inertia::render('Product/Pages/Options/Create', new CreateOptionPageData(
            storeUrl: route('larasell.admin.options.store'),
            indexUrl: route('larasell.admin.options.index'),
            displays: OptionDisplayOptionData::options(),
        ));
    }

    public function store(OptionRequest $request, #[CurrentContentLocale] Locale $locale, SaveOptionDefinition $save): RedirectResponse
    {
        $option = $save->handle(null, $request->payload($locale));

        return redirect()->route('larasell.admin.options.show', $option);
    }

    public function show(OptionDefinition $option, #[CurrentContentLocale] Locale $locale): Response
    {
        $option = Options::withUsage(OptionDefinition::query())->findOrFail($option->id);
        $usage = fn () => DB::table('larasell_product_variant_option_values as vov')
            ->join('larasell_product_option_values as pov', 'pov.id', '=', 'vov.product_option_value_id')
            ->join('larasell_product_options as po', 'po.id', '=', 'pov.product_option_id')
            ->whereColumn('pov.option_definition_value_id', 'larasell_option_definition_values.id');
        $values = $option->values()
            ->select('larasell_option_definition_values.*')
            ->selectSub($usage()->selectRaw('count(distinct po.product_id)'), 'products_count')
            ->selectSub($usage()->selectRaw('count(distinct vov.product_variant_id)'), 'variants_count')
            ->get();

        Head::title($option->name->get($locale->value));

        return Inertia::render('Product/Pages/Options/Show', new OptionPageData(
            option: OptionDetailData::fromModel($option, $locale),
            values: $values->map(fn ($value): OptionValueRowData => OptionValueRowData::fromModel($value, $locale))->all(),
            displays: OptionDisplayOptionData::options(),
            updateUrl: route('larasell.admin.options.update', $option),
            destroyUrl: route('larasell.admin.options.destroy', $option),
            indexUrl: route('larasell.admin.options.index'),
        ));
    }

    /**
     * Saves the option with all of its values: new ones are added, the others changed and the
     * ones left out deleted, unless variants use them. Names change in the content locale only.
     */
    public function update(OptionRequest $request, OptionDefinition $option, #[CurrentContentLocale] Locale $locale, SaveOptionDefinition $save): RedirectResponse
    {
        try {
            $save->handle(OptionDefinitionId::of($option), $request->payload($locale));
        } catch (OptionInUse $exception) {
            $value = $option->values()->where('slug', $exception->value)->first();

            throw ValidationException::withMessages(['values' => self::inUse($value?->name->get($locale->value) ?? (string) $exception->value, $exception)]);
        }

        return back();
    }

    /** Deletes the option with its values while no variant uses it, once the admin typed its slug. */
    public function destroy(Request $request, OptionDefinition $option, #[CurrentContentLocale] Locale $locale, DeleteOptionDefinition $delete): RedirectResponse
    {
        $request->validate(['confirmation' => [new Confirmation($option->slug)]]);

        try {
            $delete->handle(OptionDefinitionId::of($option));
        } catch (OptionInUse $exception) {
            throw ValidationException::withMessages(['option' => self::inUse($option->name->get($locale->value), $exception)]);
        }

        return redirect()->route('larasell.admin.options.index');
    }

    private static function inUse(string $name, OptionInUse $exception): string
    {
        return __(':name is used by :variants of :products. Delete those variants on the product first.', [
            'name' => $name,
            'variants' => trans_choice('{1} :count variant|[2,*] :count variants', $exception->variants),
            'products' => trans_choice('{1} :count product|[2,*] :count products', $exception->products),
        ]);
    }
}
