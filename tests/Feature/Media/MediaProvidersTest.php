<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Media\Events\MediaLibraryChanged;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Media\Actions\UploadMedia;
use Larasell\Larasell\Media\Actions\UploadMediaPayload;
use Larasell\Larasell\Media\AssetStatus;
use Larasell\Larasell\Media\AssetVisibility;
use Larasell\Larasell\Media\Models\Media;

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('private-media');
    config([
        'larasell.media.disks' => ['public' => 'media', 'private' => 'private-media'],
        'larasell.media.providers.mux' => ['driver' => 'mux', 'token_id' => 'id', 'token_secret' => 'secret', 'webhook_secret' => 'whsec'],
    ]);
    Http::fake([
        'api.mux.com/video/v1/uploads' => Http::response(['data' => ['id' => 'upload-1', 'url' => 'https://storage.mux.test/upload-1']], 201),
        'storage.mux.test/*' => Http::response('', 200),
    ]);
});

function muxUpload(AssetVisibility $visibility = AssetVisibility::Public): Media
{
    return app(UploadMedia::class)->handle(new UploadMediaPayload(UploadedFile::fake()->create('launch.mp4', 100, 'video/mp4'), visibility: $visibility, provider: 'mux'));
}

/** Posts a Mux event as Mux does, signed with the secret unless told otherwise. */
function postMuxEvent(array $event, string $secret = 'whsec'): TestResponse
{
    $body = (string) json_encode($event);
    $time = time();

    return test()->call('POST', route('larasell.admin.media.webhooks.mux', ['provider' => 'mux']), server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_MUX_SIGNATURE' => "t={$time},v1=".hash_hmac('sha256', "{$time}.{$body}", $secret),
    ], content: $body);
}

it('makes a Mux video ready from Mux\'s signed webhook, without a session, and tells the browsers', function () {
    // Arrange
    $media = muxUpload();
    Event::fake([MediaLibraryChanged::class]);

    // Act
    $response = postMuxEvent(['type' => 'video.asset.ready', 'data' => ['id' => 'asset-1', 'upload_id' => 'upload-1', 'playback_ids' => [['id' => 'play-1', 'policy' => 'public']]]]);

    // Assert
    $response->assertNoContent();
    expect($media->original->fresh()?->status)->toBe(AssetStatus::Ready);
    Event::assertDispatched(MediaLibraryChanged::class);
});

it('refuses a webhook not signed with the provider\'s secret, or for a provider that isn\'t Mux', function () {
    // Arrange
    $media = muxUpload();
    $ready = ['type' => 'video.asset.ready', 'data' => ['id' => 'asset-1', 'upload_id' => 'upload-1', 'playback_ids' => []]];

    // Act & Assert
    postMuxEvent($ready, 'forged')->assertUnauthorized();
    $this->post(route('larasell.admin.media.webhooks.mux', ['provider' => 'local']), $ready)->assertNotFound();
    expect($media->original->fresh()?->status)->toBe(AssetStatus::Processing);
});

it('says in the library and on its page that a video is still processing, with no URL yet', function () {
    // Arrange
    $media = muxUpload();
    test()->actingAs(Admin::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');

    // Act & Assert
    $this->get(route('larasell.admin.media.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entries.data.0.media.status', 'processing')
            ->where('entries.data.0.media.url', null)
            ->where('entries.data.0.media.thumbnailUrl', null));
    $this->get(route('larasell.admin.media.show', $media))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('media.status', 'processing')
            ->where('media.failure', null)
            ->where('media.url', null));
});

it('plays a private Mux video by a signed URL, not through the panel', function () {
    // Arrange
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    openssl_pkey_export($key, $pem);
    config(['larasell.media.providers.mux.signing_key_id' => 'key-1', 'larasell.media.providers.mux.signing_key' => base64_encode($pem)]);
    $media = muxUpload(AssetVisibility::Private);
    postMuxEvent(['type' => 'video.asset.ready', 'data' => ['id' => 'asset-1', 'upload_id' => 'upload-1', 'playback_ids' => [['id' => 'play-1', 'policy' => 'signed']]]]);
    test()->actingAs(Admin::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');

    // Act
    $response = $this->get(route('larasell.admin.media.file', $media));

    // Assert
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://stream.mux.com/play-1.m3u8?token=');
    // The page hands the player the stream itself, which it must know is HLS.
    $this->get(route('larasell.admin.media.show', $media))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('media.url', fn (string $url): bool => str_starts_with($url, 'https://stream.mux.com/play-1.m3u8?token=')));
});
