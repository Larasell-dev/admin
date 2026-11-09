<?php

namespace Larasell\Admin\SalesChannel\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\SalesChannel\Actions\AddEntryPoint;
use Larasell\Admin\SalesChannel\Actions\DeleteEntryPoint;
use Larasell\Admin\SalesChannel\Http\Requests\AddEntryPointRequest;
use Larasell\Admin\SalesChannel\Http\Requests\DeleteEntryPointRequest;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;

/** The domains of a storefront channel, listed and added on the channel's page. */
class EntryPointController
{
    public function store(AddEntryPointRequest $request, SalesChannel $channel, AddEntryPoint $add): RedirectResponse
    {
        $add->handle($channel, $request->payload());

        return redirect()->route('larasell.admin.sales-channels.edit', $channel);
    }

    public function destroy(DeleteEntryPointRequest $request, SalesChannel $channel, DeleteEntryPoint $delete): RedirectResponse
    {
        $delete->handle($request->entryPoint());

        return redirect()->route('larasell.admin.sales-channels.edit', $channel);
    }
}
