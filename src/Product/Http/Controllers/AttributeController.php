<?php

namespace Larasell\Admin\Product\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;
use Larasell\Admin\Product\Data\AttributeDetailData;
use Larasell\Admin\Product\Data\AttributePageData;
use Larasell\Admin\Product\Data\AttributeScopeOptionData;
use Larasell\Admin\Product\Data\AttributeValueRowData;
use Larasell\Admin\Product\Data\CreateAttributePageData;
use Larasell\Admin\Product\Http\Requests\AttributeRequest;
use Larasell\Admin\Product\Tables\Attributes;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Catalog\Actions\DeleteProductAttribute;
use Larasell\Larasell\Catalog\Actions\SaveProductAttribute;
use Larasell\Larasell\Catalog\AttributeId;
use Larasell\Larasell\Catalog\Models\ProductAttribute;
use Larasell\Larasell\Enums\Locale;
use Laravel\Head\Facades\Head;

/** The catalog's attributes, which describe products and variants without creating variants. */
class AttributeController
{
    public function create(): Response
    {
        return Inertia::render('Product/Pages/Attributes/Create', new CreateAttributePageData(
            storeUrl: route('larasell.admin.attributes.store'),
            indexUrl: route('larasell.admin.attributes.index'),
            scopes: AttributeScopeOptionData::options(),
        ));
    }

    /** Creates a text attribute with its values, named in the content locale. */
    public function store(AttributeRequest $request, #[CurrentContentLocale] Locale $locale, SaveProductAttribute $save, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $attribute = $save->handle(null, $request->payload($locale), $origin);

        return redirect()->route('larasell.admin.attributes.show', $attribute);
    }

    public function show(ProductAttribute $attribute, #[CurrentContentLocale] Locale $locale): Response
    {
        $attribute = Attributes::withUsage(ProductAttribute::query())->findOrFail($attribute->id);
        $values = $attribute->values()
            ->withCount('products')
            ->selectSub(DB::table('larasell_product_variant_attribute_values')
                ->whereColumn('product_attribute_value_id', 'larasell_product_attribute_values.id')
                ->selectRaw('count(distinct product_variant_id)'), 'variants_count')
            ->orderByRaw('position is null')->orderBy('position')->orderBy('id')
            ->get();

        Head::title($attribute->name->get($locale->value));

        return Inertia::render('Product/Pages/Attributes/Show', new AttributePageData(
            attribute: AttributeDetailData::fromModel($attribute, $locale),
            values: $values->map(fn ($value): AttributeValueRowData => AttributeValueRowData::fromModel($value, $locale))->all(),
            scopes: AttributeScopeOptionData::options(),
            updateUrl: route('larasell.admin.attributes.update', $attribute),
            destroyUrl: route('larasell.admin.attributes.destroy', $attribute),
            indexUrl: route('larasell.admin.attributes.index'),
        ));
    }

    /**
     * Saves the attribute with all of its values: new ones are added, the others changed and the
     * ones left out deleted. Names change in the content locale only.
     */
    public function update(AttributeRequest $request, ProductAttribute $attribute, #[CurrentContentLocale] Locale $locale, SaveProductAttribute $save, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $save->handle(AttributeId::of($attribute), $request->payload($locale), $origin);

        return back();
    }

    /**
     * Deletes the attribute with its values, which products and variants lose. The admin
     * confirms by typing its slug.
     */
    public function destroy(Request $request, ProductAttribute $attribute, DeleteProductAttribute $delete, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $request->validate(['confirmation' => [new Confirmation($attribute->slug)]]);

        $delete->handle(AttributeId::of($attribute), $origin);

        return redirect()->route('larasell.admin.attributes.index');
    }
}
