<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Media\MediaProviders;
use Larasell\Larasell\Media\MediaType;

beforeEach(function () {
    config(['larasell.media.provider_for.video' => null, 'larasell.media.providers.mux' => ['driver' => 'mux']]);
    test()->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

function saveMux(array $fields = []): TestResponse
{
    return test()->put(route('larasell.admin.settings.mux.update'), [
        'token_id' => 'token-id',
        'token_secret' => 'token-secret',
        'webhook_secret' => 'whsec',
        'signing_key_id' => 'key-id',
        'signing_key' => 'private-key',
        'use_for_videos' => '1',
        ...$fields,
    ]);
}

it('lists Mux among the settings', function () {
    // Act & Assert
    test()->get(route('larasell.admin.settings'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('settingsPages', fn ($pages) => collect($pages)->pluck('id')->all() === ['team', 'api-tokens', 'audit-log', 'mux']));
});

it('saves the Mux account, which uploads and webhooks then use', function () {
    // Act
    saveMux()->assertRedirect(route('larasell.admin.settings.mux.edit'));

    // Assert
    expect(app(MediaProviders::class)->config('mux'))->toMatchArray([
        'token_id' => 'token-id',
        'token_secret' => 'token-secret',
        'webhook_secret' => 'whsec',
        'signing_key_id' => 'key-id',
        'signing_key' => 'private-key',
    ])->and(app(MediaProviders::class)->nameFor(MediaType::Video))->toBe('mux');
});

it('shows which secrets are saved, never the secrets, and the webhook URL to give Mux', function () {
    // Arrange
    saveMux(['signing_key' => '']);

    // Act & Assert
    test()->get(route('larasell.admin.settings.mux.edit'))
        ->assertOk()
        ->assertDontSee('token-secret')
        ->assertDontSee('whsec')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Media/Pages/MuxSettings')
            ->where('settings.tokenId', 'token-id')
            ->where('settings.signingKeyId', 'key-id')
            ->where('settings.hasTokenSecret', true)
            ->where('settings.hasWebhookSecret', true)
            ->where('settings.hasSigningKey', false)
            ->where('settings.useForVideos', true)
            ->where('settings.webhookUrl', route('larasell.admin.media.webhooks.mux', ['provider' => 'mux'])));
});

it('keeps a saved secret when its field is left empty', function () {
    // Arrange
    saveMux();

    // Act
    saveMux(['token_id' => 'other-id', 'token_secret' => '', 'use_for_videos' => '0']);

    // Assert
    expect(app(MediaProviders::class)->config('mux'))->toMatchArray(['token_id' => 'other-id', 'token_secret' => 'token-secret'])
        ->and(app(MediaProviders::class)->nameFor(MediaType::Video))->toBe('local');
});

it('asks for a token secret the first time', function () {
    // Act & Assert
    saveMux(['token_secret' => ''])->assertSessionHasErrors('token_secret');
    saveMux(['token_id' => ''])->assertSessionHasErrors('token_id');
});
