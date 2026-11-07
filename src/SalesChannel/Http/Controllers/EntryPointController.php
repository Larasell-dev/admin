<?php

namespace Larasell\Admin\SalesChannel\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Larasell\Admin\SalesChannel\Actions\AddEntryPoint;
use Larasell\Admin\SalesChannel\Actions\DeleteEntryPoint;
use Larasell\Admin\SalesChannel\Http\Requests\AddEntryPointRequest;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\Storefront\Models\EntryPoint;

/** The domains of a storefront channel, listed and added on the channel's page. */
class EntryPointController
{
    public function store(AddEntryPointRequest $request, SalesChannel $channel, AddEntryPoint $add): RedirectResponse
    {
        $add->handle($channel, $request->payload());

        return redirect()->route('larasell.admin.sales-channels.edit', $channel);
    }

    public function destroy(Request $request, SalesChannel $channel, int $entryPoint, DeleteEntryPoint $delete): RedirectResponse
    {
        $delete->handle(EntryPoint::query()->where('sales_channel_id', $channel->id)->findOrFail($entryPoint), $request->string('confirmation')->value() ?: null);

        return redirect()->route('larasell.admin.sales-channels.edit', $channel);
    }
}
