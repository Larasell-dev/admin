<?php

namespace Larasell\Admin\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Larasell\Media\Actions\UpdateMuxSettingsPayload;
use Larasell\Larasell\Media\MediaProviders;
use Larasell\Larasell\Media\MuxSettings;

/** The Mux account. An empty secret keeps the saved one, so the page never needs to show it. */
class UpdateMuxSettingsRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        // Saved in the panel or set in the config file.
        $secret = app(MediaProviders::class)->config(MuxSettings::PROVIDER)['token_secret'] ?? null;

        return [
            'token_id' => ['required', 'string', 'max:255'],
            'token_secret' => [is_string($secret) && $secret !== '' ? 'nullable' : 'required', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
            'signing_key_id' => ['nullable', 'string', 'max:255'],
            'signing_key' => ['nullable', 'string', 'max:8192'],
            'use_for_videos' => ['required', 'boolean'],
        ];
    }

    public function toPayload(): UpdateMuxSettingsPayload
    {
        return new UpdateMuxSettingsPayload(
            tokenId: $this->string('token_id')->trim()->toString(),
            tokenSecret: $this->filled('token_secret') ? $this->string('token_secret')->trim()->toString() : null,
            webhookSecret: $this->filled('webhook_secret') ? $this->string('webhook_secret')->trim()->toString() : null,
            signingKeyId: $this->filled('signing_key_id') ? $this->string('signing_key_id')->trim()->toString() : null,
            signingKey: $this->filled('signing_key') ? $this->string('signing_key')->trim()->toString() : null,
            useForVideos: $this->boolean('use_for_videos'),
        );
    }
}
