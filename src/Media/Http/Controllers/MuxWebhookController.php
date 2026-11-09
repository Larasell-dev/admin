<?php

namespace Larasell\Admin\Media\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Larasell\Larasell\Media\Actions\HandleMuxWebhook;
use Larasell\Larasell\Media\MediaProviders;
use Larasell\Larasell\Media\MuxMediaStorage;

/**
 * Where Mux says what became of a video, e.g. that it is ready to play. Outside the panel's
 * sign-in and CSRF check, as Mux has neither; it proves itself by signing each request with the
 * provider's webhook secret instead.
 */
class MuxWebhookController
{
    public function __invoke(Request $request, string $provider, MediaProviders $providers, HandleMuxWebhook $handle): Response
    {
        abort_unless(in_array($provider, $providers->namesWithDriver('mux'), true), 404);
        $mux = $providers->provider($provider);
        abort_unless($mux instanceof MuxMediaStorage, 404);
        abort_unless($mux->webhookSignature()->valid($request->getContent(), (string) $request->header('Mux-Signature')), 401);

        /** @var array{type?: string, data?: array{id?: string, upload_id?: string, asset_id?: string, status?: string, playback_ids?: list<array{id: string, policy: string}>, tracks?: list<array<string, mixed>>, errors?: array{messages?: list<string>}}} $event */
        $event = $request->json()->all();
        $handle->handle($event);

        return response()->noContent();
    }
}
