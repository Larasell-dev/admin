<?php

namespace Larasell\Admin\Media\Data;

use Larasell\Larasell\Media\MediaProviders;
use Larasell\Larasell\Media\MediaType;
use Larasell\Larasell\Media\MuxSettings;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The Mux account in effect, saved in the panel or from the config file, as the settings page
 * shows it: the IDs, and only whether each secret is set.
 */
#[TypeScript]
class MuxSettingsData extends Data
{
    public function __construct(
        public ?string $tokenId,
        public bool $hasTokenSecret,
        public bool $hasWebhookSecret,
        public ?string $signingKeyId,
        public bool $hasSigningKey,
        public bool $useForVideos,
        /** Where Mux sends its webhooks, to paste into Mux's dashboard. */
        public string $webhookUrl,
        public string $updateUrl,
    ) {}

    public static function current(): self
    {
        $config = app(MediaProviders::class)->config(MuxSettings::PROVIDER);
        $value = fn (string $key): ?string => is_string($config[$key] ?? null) && $config[$key] !== '' ? $config[$key] : null;

        return new self(
            tokenId: $value('token_id'),
            hasTokenSecret: $value('token_secret') !== null,
            hasWebhookSecret: $value('webhook_secret') !== null,
            signingKeyId: $value('signing_key_id'),
            hasSigningKey: $value('signing_key') !== null,
            useForVideos: app(MediaProviders::class)->nameFor(MediaType::Video) === MuxSettings::PROVIDER,
            webhookUrl: route('larasell.admin.media.webhooks.mux', ['provider' => MuxSettings::PROVIDER]),
            updateUrl: route('larasell.admin.settings.mux.update'),
        );
    }
}
