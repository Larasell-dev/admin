<?php

namespace Larasell\Admin\Manufacturer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as NoContent;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Manufacturer\Data\CountryOptionData;
use Larasell\Admin\Manufacturer\Data\CreateManufacturerPageData;
use Larasell\Admin\Manufacturer\Data\ManufacturerDetailData;
use Larasell\Admin\Manufacturer\Data\ManufacturerPageData;
use Larasell\Admin\Manufacturer\Http\Requests\ManufacturerRequest;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Catalog\Manufacturers\Actions\CreateManufacturer;
use Larasell\Larasell\Catalog\Manufacturers\Actions\DeleteManufacturer;
use Larasell\Larasell\Catalog\Manufacturers\Actions\UpdateManufacturer;
use Larasell\Larasell\Catalog\Manufacturers\Exceptions\ManufacturerInUse;
use Larasell\Larasell\Catalog\Manufacturers\ManufacturerId;
use Larasell\Larasell\Catalog\Manufacturers\Models\Manufacturer;
use Laravel\Head\Facades\Head;

/** The legal producers of products. A manufacturer products use can't be deleted. */
class ManufacturerController
{
    public function create(): Response
    {
        return Inertia::render('Manufacturer/Pages/Create', new CreateManufacturerPageData(
            countries: CountryOptionData::options(),
            storeUrl: route('larasell.admin.manufacturers.store'),
            indexUrl: route('larasell.admin.manufacturers.index'),
        ));
    }

    public function store(ManufacturerRequest $request, CreateManufacturer $create, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $manufacturer = $create->handle($request->createPayload(), $origin);

        return redirect()->route('larasell.admin.manufacturers.show', $manufacturer);
    }

    /** Checks a manufacturer added or changed on the product form and saves nothing. Answers 204, or 422 with the errors. */
    public function check(Request $request): NoContent
    {
        $request->validate(ManufacturerRequest::newRules(), [], ManufacturerRequest::newAttributes());

        return response()->noContent();
    }

    public function show(Manufacturer $manufacturer): Response
    {
        $manufacturer->loadCount('products');
        Head::title($manufacturer->name);

        return Inertia::render('Manufacturer/Pages/Show', new ManufacturerPageData(
            manufacturer: ManufacturerDetailData::fromModel($manufacturer),
            countries: CountryOptionData::options(),
            updateUrl: route('larasell.admin.manufacturers.update', $manufacturer),
            destroyUrl: route('larasell.admin.manufacturers.destroy', $manufacturer),
            indexUrl: route('larasell.admin.manufacturers.index'),
        ));
    }

    public function update(ManufacturerRequest $request, Manufacturer $manufacturer, UpdateManufacturer $update, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $update->handle(ManufacturerId::of($manufacturer), $request->updatePayload(), $origin);

        return back();
    }

    /** Deletes the manufacturer once the admin typed its name, while no product uses it. */
    public function destroy(Request $request, Manufacturer $manufacturer, DeleteManufacturer $delete, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $request->validate(['confirmation' => [new Confirmation($manufacturer->name)]]);

        try {
            $delete->handle(ManufacturerId::of($manufacturer), $origin);
        } catch (ManufacturerInUse $exception) {
            throw ValidationException::withMessages(['manufacturer' => trans_choice('{1} Used by :count product. Give it another manufacturer first.|[2,*] Used by :count products. Give them another manufacturer first.', $exception->products)]);
        }

        return redirect()->route('larasell.admin.manufacturers.index');
    }
}
