<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Jobs\Media\GenerateProductImageThumbnail;
use Larasell\Admin\Models\Media\ProductImageThumbnail;
use Larasell\Larasell\Models\ProductImage;

beforeEach(function () {
    Storage::fake(config('larasell.images.disk'));
    // Uploads queue their own thumbnails; these tests run the job themselves.
    Queue::fake();
});

function thumbnailOf(ProductImage $image): ?ProductImageThumbnail
{
    return ProductImageThumbnail::query()->where('product_image_id', $image->id)->first();
}

it('stores a square webp thumbnail of the image and records it', function (int $width, int $height, int $edge) {
    $image = ProductImage::create(['file' => UploadedFile::fake()->image('apple.jpg', $width, $height)]);

    (new GenerateProductImageThumbnail($image))->handle();

    $thumbnail = thumbnailOf($image);
    $disk = Storage::disk(config('larasell.images.disk'));
    $disk->assertExists($thumbnail->path);
    expect($thumbnail->path)->not->toBe($image->file)
        ->and(getimagesizefromstring($disk->get($thumbnail->path)))
        ->toMatchArray([0 => $edge, 1 => $edge, 2 => IMAGETYPE_WEBP]);
})->with([
    'landscape, cropped' => [800, 400, 96],
    'portrait, cropped' => [300, 900, 96],
    // Never enlarged: a 96 px thumbnail of a 40 px image only costs more bytes.
    'smaller than a thumbnail' => [60, 40, 40],
]);

it('stores no thumbnail for a file it cannot read as an image', function () {
    $image = ProductImage::create(['file' => UploadedFile::fake()->createWithContent('broken.png', 'not a png')]);

    (new GenerateProductImageThumbnail($image))->handle();

    expect(thumbnailOf($image))->toBeNull()
        ->and(Storage::disk(config('larasell.images.disk'))->allFiles())->toBe([$image->file]);
});

it('replaces an earlier thumbnail instead of keeping both', function () {
    $image = ProductImage::create(['file' => UploadedFile::fake()->image('apple.jpg', 200, 200)]);

    (new GenerateProductImageThumbnail($image))->handle();
    $first = thumbnailOf($image)->path;
    (new GenerateProductImageThumbnail($image))->handle();

    expect(ProductImageThumbnail::query()->count())->toBe(1)
        ->and(Storage::disk(config('larasell.images.disk'))->allFiles())->toEqualCanonicalizing([$image->file, $first])
        ->and(thumbnailOf($image)->path)->toBe($first);
});

it('queues thumbnails for the images that have none, from the command', function () {
    $with = ProductImage::create(['file' => UploadedFile::fake()->image('a.jpg')]);
    $without = ProductImage::create(['file' => UploadedFile::fake()->image('b.jpg')]);
    ProductImageThumbnail::create(['product_image_id' => $with->id, 'path' => 'thumbs/a.webp']);

    $this->artisan('larasell:media-thumbnails')->assertSuccessful();

    Queue::assertPushed(GenerateProductImageThumbnail::class, 1);
    Queue::assertPushed(GenerateProductImageThumbnail::class, fn (GenerateProductImageThumbnail $job): bool => $job->image->is($without));
});
