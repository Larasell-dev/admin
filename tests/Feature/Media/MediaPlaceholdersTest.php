<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Media\Jobs\GeneratePlaceholder;
use Larasell\Admin\Media\Models\MediaPlaceholder;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Media\Actions\DeleteMedia;
use Larasell\Larasell\Media\Actions\UploadMedia;
use Larasell\Larasell\Media\Actions\UploadMediaPayload;
use Larasell\Larasell\Media\AssetVisibility;
use Larasell\Larasell\Media\Events\MediaThumbnailGenerated;
use Larasell\Larasell\Media\Exceptions\MediaNotShared;
use Larasell\Larasell\Media\Jobs\GenerateThumbnail;
use Larasell\Larasell\Media\MediaId;
use Larasell\Larasell\Media\Models\Media;
use Thumbhash\Thumbhash;

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('private-media');
    config(['larasell.media.disks' => ['public' => 'media', 'private' => 'private-media']]);
});

/** A PNG of one color, so the placeholder's color can be checked. */
function solidImage(string $name, int $width, int $height, array $rgb): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));
    $path = tempnam(sys_get_temp_dir(), 'solid').'.png';
    imagepng($image, $path);

    return new UploadedFile($path, $name, 'image/png', null, true);
}

function uploadPlaceholderMedia(UploadedFile $file): Media
{
    return app(UploadMedia::class)->handle(new UploadMediaPayload($file, visibility: AssetVisibility::Private));
}

it('queues a placeholder for an uploaded image on the media queue', function () {
    // Arrange
    Queue::fake();
    config(['larasell.admin.media.queue' => 'images']);

    // Act
    $media = uploadPlaceholderMedia(UploadedFile::fake()->image('boots.png'));

    // Assert
    Queue::assertPushedOn('images', GeneratePlaceholder::class, fn (GeneratePlaceholder $job): bool => $job->mediaId->equals(MediaId::of($media)));
});

it('waits for a video\'s poster before queueing its placeholder', function () {
    // Arrange
    Queue::fake();

    // Act
    $media = uploadPlaceholderMedia(UploadedFile::fake()->create('unboxing.mp4', 100, 'video/mp4'));
    $beforePoster = Queue::pushed(GeneratePlaceholder::class)->count();
    MediaThumbnailGenerated::dispatch(MediaId::of($media));

    // Assert
    expect($beforePoster)->toBe(0);
    Queue::assertPushed(GeneratePlaceholder::class, fn (GeneratePlaceholder $job): bool => $job->mediaId->equals(MediaId::of($media)));
});

it('makes a video\'s placeholder from its poster', function () {
    // Arrange
    Queue::fake();
    $path = tempnam(sys_get_temp_dir(), 'video').'.mp4';
    exec('ffmpeg -loglevel error -y -f lavfi -i color=c=red:s=640x320:d=1 -pix_fmt yuv420p '.escapeshellarg($path));
    $media = uploadPlaceholderMedia(new UploadedFile($path, 'unboxing.mp4', 'video/mp4', null, true));
    app()->call([new GenerateThumbnail(MediaId::of($media)), 'handle']);

    // Act
    app()->call([new GeneratePlaceholder(MediaId::of($media)), 'handle']);

    // Assert
    $hash = Thumbhash::convertStringToHash((string) MediaPlaceholder::query()->where('media_id', $media->id)->value('thumbhash'));
    expect(Thumbhash::toApproximateAspectRatio($hash))->toEqualWithDelta(2.0, 0.25);
    $decoded = Thumbhash::hashToRGBA($hash);
    $center = (intdiv((int) $decoded['h'], 2) * (int) $decoded['w'] + intdiv((int) $decoded['w'], 2)) * 4;
    expect($decoded['rgba'][$center])->toBeGreaterThan(170)
        ->and($decoded['rgba'][$center + 1])->toBeLessThan(80);
});

it('leaves a video without a poster without a placeholder', function () {
    // Arrange
    Queue::fake();
    $media = uploadPlaceholderMedia(UploadedFile::fake()->create('unboxing.mp4', 100, 'video/mp4'));

    // Act
    app()->call([new GeneratePlaceholder(MediaId::of($media)), 'handle']);

    // Assert
    expect(MediaPlaceholder::query()->count())->toBe(0);
});

it('stores a ThumbHash that keeps the image\'s color and shape', function () {
    // Arrange
    Queue::fake();
    $media = uploadPlaceholderMedia(solidImage('red.png', 300, 150, [220, 20, 30]));

    // Act
    app()->call([new GeneratePlaceholder(MediaId::of($media)), 'handle']);

    // Assert
    $hash = Thumbhash::convertStringToHash((string) MediaPlaceholder::query()->where('media_id', $media->id)->value('thumbhash'));
    expect(Thumbhash::toApproximateAspectRatio($hash))->toEqualWithDelta(2.0, 0.25);
    $decoded = Thumbhash::hashToRGBA($hash);
    $center = (intdiv((int) $decoded['h'], 2) * (int) $decoded['w'] + intdiv((int) $decoded['w'], 2)) * 4;
    $rgba = array_slice($decoded['rgba'], $center, 4);
    expect($rgba[0])->toBeGreaterThan(170)
        ->and($rgba[1])->toBeLessThan(80)
        ->and($rgba[2])->toBeLessThan(80);
});

it('replaces the placeholder it made before', function () {
    // Arrange
    Queue::fake();
    $media = uploadPlaceholderMedia(solidImage('red.png', 100, 100, [220, 20, 30]));
    app()->call([new GeneratePlaceholder(MediaId::of($media)), 'handle']);

    // Act
    app()->call([new GeneratePlaceholder(MediaId::of($media)), 'handle']);

    // Assert
    expect(MediaPlaceholder::query()->where('media_id', $media->id)->count())->toBe(1);
});

it('fails loudly when the worker cannot reach the original, e.g. on a disk that is not shared', function () {
    // Arrange
    Queue::fake();
    $media = uploadPlaceholderMedia(UploadedFile::fake()->image('boots.png'));
    Storage::disk('private-media')->delete($media->original->path);

    // Act & Assert
    expect(fn () => app()->call([new GeneratePlaceholder(MediaId::of($media)), 'handle']))
        ->toThrow(MediaNotShared::class);
    expect(MediaPlaceholder::query()->count())->toBe(0);
});

it('does nothing for media deleted before the job ran', function () {
    // Arrange
    Queue::fake();
    $media = uploadPlaceholderMedia(UploadedFile::fake()->image('boots.png'));
    app(DeleteMedia::class)->handle(MediaId::of($media));

    // Act
    app()->call([new GeneratePlaceholder(MediaId::of($media)), 'handle']);

    // Assert
    expect(MediaPlaceholder::query()->count())->toBe(0);
});

it('hands the grid each item\'s placeholder', function () {
    // Arrange
    test()->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
    $red = uploadPlaceholderMedia(solidImage('red.png', 100, 100, [220, 20, 30]));
    $video = uploadPlaceholderMedia(UploadedFile::fake()->create('unboxing.mp4', 100, 'video/mp4'));
    $hash = MediaPlaceholder::query()->where('media_id', $red->id)->value('thumbhash');

    // Act & Assert
    expect($hash)->toBeString()->not->toBe('');
    $this->get(route('larasell.admin.media.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('entries.data.0.media.id', $video->id)
            ->where('entries.data.0.media.thumbHash', null)
            ->where('entries.data.1.media.thumbHash', $hash));
});

it('queues placeholders for the images that have none yet', function () {
    // Arrange
    $done = uploadPlaceholderMedia(UploadedFile::fake()->image('done.png'));
    Queue::fake();
    $missing = uploadPlaceholderMedia(UploadedFile::fake()->image('missing.png'));
    uploadPlaceholderMedia(UploadedFile::fake()->create('unboxing.mp4', 100, 'video/mp4'));
    Queue::fake();

    // Act & Assert
    $this->artisan('larasell:generate-media-placeholders')
        ->expectsOutputToContain('Queued 1 placeholder')
        ->assertSuccessful();
    Queue::assertPushed(GeneratePlaceholder::class, 1);
    Queue::assertPushed(GeneratePlaceholder::class, fn (GeneratePlaceholder $job): bool => $job->mediaId->equals(MediaId::of($missing)));
    expect(MediaPlaceholder::query()->where('media_id', $done->id)->exists())->toBeTrue();
});

it('queues placeholders for every image when forced', function () {
    // Arrange
    uploadPlaceholderMedia(UploadedFile::fake()->image('done.png'));
    Queue::fake();
    uploadPlaceholderMedia(UploadedFile::fake()->image('missing.png'));
    Queue::fake();

    // Act & Assert
    $this->artisan('larasell:generate-media-placeholders', ['--force' => true])->assertSuccessful();
    Queue::assertPushed(GeneratePlaceholder::class, 2);
});
