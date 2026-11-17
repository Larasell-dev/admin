<?php

namespace Larasell\Admin\Brand\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as NoContent;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Brand\Data\BrandDetailData;
use Larasell\Admin\Brand\Data\BrandPageData;
use Larasell\Admin\Brand\Data\CreateBrandPageData;
use Larasell\Admin\Brand\Http\Requests\BrandRequest;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Catalog\Brands\Actions\CreateBrand;
use Larasell\Larasell\Catalog\Brands\Actions\DeleteBrand;
use Larasell\Larasell\Catalog\Brands\Actions\UpdateBrand;
use Larasell\Larasell\Catalog\Brands\BrandId;
use Larasell\Larasell\Catalog\Brands\Exceptions\BrandInUse;
use Larasell\Larasell\Catalog\Brands\Models\Brand;
use Larasell\Larasell\Enums\Locale;
use Laravel\Head\Facades\Head;

/** The brands products are marketed under. A brand products use can't be deleted. */
class BrandController
{
    public function create(): Response
    {
        return Inertia::render('Brand/Pages/Create', new CreateBrandPageData(
            storeUrl: route('larasell.admin.brands.store'),
            indexUrl: route('larasell.admin.brands.index'),
        ));
    }

    public function store(BrandRequest $request, #[CurrentContentLocale] Locale $locale, CreateBrand $create, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $brand = $create->handle($request->createPayload($locale->value), $origin);

        return redirect()->route('larasell.admin.brands.show', $brand);
    }

    /**
     * Checks a brand added or changed on the product form, ?brand= the one changed, and saves
     * nothing: the product saves it. Answers 204, or 422 with the errors.
     */
    public function check(Request $request): NoContent
    {
        $request->validate(BrandRequest::newRules('', Brand::query()->find($request->integer('brand'))));

        return response()->noContent();
    }

    public function show(Brand $brand, #[CurrentContentLocale] Locale $locale): Response
    {
        $brand->loadCount('products');
        $data = BrandDetailData::fromModel($brand, $locale);
        Head::title($data->name ?? $data->fallbackName ?? $brand->slug);

        return Inertia::render('Brand/Pages/Show', new BrandPageData(
            brand: $data,
            updateUrl: route('larasell.admin.brands.update', $brand),
            destroyUrl: route('larasell.admin.brands.destroy', $brand),
            indexUrl: route('larasell.admin.brands.index'),
        ));
    }

    public function update(BrandRequest $request, Brand $brand, #[CurrentContentLocale] Locale $locale, UpdateBrand $update, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $update->handle(BrandId::of($brand), $request->updatePayload($brand, $locale->value), $origin);

        return back();
    }

    /** Deletes the brand once the admin typed its slug, while no product uses it. */
    public function destroy(Request $request, Brand $brand, DeleteBrand $delete, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $request->validate(['confirmation' => [new Confirmation($brand->slug)]]);

        try {
            $delete->handle(BrandId::of($brand), $origin);
        } catch (BrandInUse $exception) {
            throw ValidationException::withMessages(['brand' => trans_choice('{1} Used by :count product. Give it another brand first.|[2,*] Used by :count products. Give them another brand first.', $exception->products)]);
        }

        return redirect()->route('larasell.admin.brands.index');
    }
}
