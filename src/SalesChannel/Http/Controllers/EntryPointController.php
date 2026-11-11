<?php

namespace Larasell\Admin\SalesChannel\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Admin\SalesChannel\Actions\AddEntryPoint;
use Larasell\Admin\SalesChannel\Actions\DeleteEntryPoint;
use Larasell\Admin\SalesChannel\Http\Requests\AddEntryPointRequest;
use Larasell\Admin\SalesChannel\Http\Requests\DeleteEntryPointRequest;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;

/** The domains of a storefront channel, listed and added on the channel's page. */
class EntryPointController
{
    public function store(AddEntryPointRequest $request, SalesChannel $channel, AddEntryPoint $add, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $add->handle($channel, $request->payload(), $origin);

        return redirect()->route('larasell.admin.sales-channels.edit', $channel);
    }

    public function destroy(DeleteEntryPointRequest $request, SalesChannel $channel, DeleteEntryPoint $delete, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $delete->handle($request->entryPoint(), $origin);

        // Back to the page of domains it was on; past the end, the channel's page shows the last one.
        return back(fallback: route('larasell.admin.sales-channels.edit', $channel));
    }
}
