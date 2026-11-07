<?php

namespace Larasell\Admin\SalesChannel\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\SalesChannel\Actions\CreateSalesChannel;
use Larasell\Admin\SalesChannel\Actions\DeleteSalesChannel;
use Larasell\Admin\SalesChannel\Actions\UpdateSalesChannel;
use Larasell\Admin\SalesChannel\Data\CreateSalesChannelPageData;
use Larasell\Admin\SalesChannel\Data\EditSalesChannelPageData;
use Larasell\Admin\SalesChannel\Data\EntryPointData;
use Larasell\Admin\SalesChannel\Data\SalesChannelData;
use Larasell\Admin\SalesChannel\Data\SalesChannelNavigationData;
use Larasell\Admin\SalesChannel\Data\SalesChannelOptionData;
use Larasell\Admin\SalesChannel\Http\Requests\CreateSalesChannelRequest;
use Larasell\Admin\SalesChannel\Http\Requests\UpdateSalesChannelRequest;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\SalesChannels\SalesChannelTypes;
use Larasell\Larasell\Storefront\Models\EntryPoint;
use Larasell\Larasell\Storefront\StorefrontContext;
use Laravel\Head\Facades\Head;

class SalesChannelController
{
    public function create(SalesChannelTypes $types): Response
    {
        return Inertia::render('SalesChannel/Pages/Create', new CreateSalesChannelPageData(
            storeUrl: route('larasell.admin.sales-channels.store'),
            types: $types->all(),
            locales: SalesChannelOptionData::locales(),
            currencies: SalesChannelOptionData::currencies(),
        ));
    }

    public function store(CreateSalesChannelRequest $request, CreateSalesChannel $create): RedirectResponse
    {
        $channel = $create->handle($request->createPayload());
        SalesChannelNavigationData::refreshOnNextPage();

        return redirect()->route('larasell.admin.sales-channels.edit', $channel);
    }

    public function edit(SalesChannel $channel): Response
    {
        Head::title($channel->name);
        $storefront = $channel->type === StorefrontContext::TYPE;

        return Inertia::render('SalesChannel/Pages/Edit', new EditSalesChannelPageData(
            channel: SalesChannelData::fromModel($channel),
            updateUrl: route('larasell.admin.sales-channels.update', $channel),
            destroyUrl: route('larasell.admin.sales-channels.destroy', $channel),
            locales: SalesChannelOptionData::locales(),
            currencies: SalesChannelOptionData::currencies(),
            entryPoints: $storefront ? EntryPoint::query()
                ->where('sales_channel_id', $channel->id)
                ->orderBy('locale')->orderByDesc('is_primary')->orderBy('id')
                ->get()->map(EntryPointData::fromModel(...))->all() : null,
            addEntryPointUrl: $storefront ? route('larasell.admin.sales-channels.entry-points.store', $channel) : null,
        ));
    }

    public function update(UpdateSalesChannelRequest $request, SalesChannel $channel, UpdateSalesChannel $update): RedirectResponse
    {
        $update->handle($channel, $request->payload());
        SalesChannelNavigationData::refreshOnNextPage();

        return redirect()->route('larasell.admin.sales-channels.edit', $channel);
    }

    public function destroy(Request $request, SalesChannel $channel, DeleteSalesChannel $delete): RedirectResponse
    {
        $delete->handle($channel, $request->string('confirmation')->value() ?: null);
        SalesChannelNavigationData::refreshOnNextPage();

        return redirect()->route('larasell.admin.home');
    }
}
