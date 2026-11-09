<?php

namespace Larasell\Admin\Media\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Media\Data\MuxSettingsData;
use Larasell\Admin\Media\Http\Requests\UpdateMuxSettingsRequest;
use Larasell\Larasell\Media\Actions\UpdateMuxSettings;

/** The settings page for the Mux account videos go to. */
class MuxSettingsController
{
    public function edit(): Response
    {
        return Inertia::render('Media/Pages/MuxSettings', ['settings' => MuxSettingsData::current()]);
    }

    public function update(UpdateMuxSettingsRequest $request, UpdateMuxSettings $update): RedirectResponse
    {
        $update->handle($request->toPayload());

        return to_route('larasell.admin.settings.mux.edit');
    }
}
