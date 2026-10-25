<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Actions\Media\DeleteProductImage;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Admin\Models\Media\ProductImageThumbnail;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Models\ProductImage;
use Larasell\Larasell\Price;
use Larasell\Larasell\Translatable;

beforeEach(function () {
    Storage::fake(config('larasell.images.disk'));
});

function storedProductImage(string $name): ProductImage
{
    return ProductImage::create(['file' => UploadedFile::fake()->image($name)]);
}

it('deletes the product image and its file', function () {
    $image = storedProductImage('apple.jpg');
    $other = storedProductImage('mango.jpg');
    $disk = Storage::disk(config('larasell.images.disk'));

    app(DeleteProductImage::class)->handle($image);

    expect(ProductImage::query()->pluck('id')->all())->toBe([$other->id]);
    $disk->assertMissing($image->file);
    $disk->assertExists($other->file);
});

it('removes the image from its products but keeps the products', function () {
    $image = storedProductImage('apple.jpg');
    $other = storedProductImage('mango.jpg');
    $product = Product::create([
        'name' => Translatable::fromString('Runner'),
        'slug' => Translatable::fromString('runner'),
        'price' => Price::of(1000),
    ]);
    $product->images()->attach([$image->id => ['position' => 1], $other->id => ['position' => 2]]);

    app(DeleteProductImage::class)->handle($image);

    expect(Product::query()->whereKey($product->id)->exists())->toBeTrue()
        ->and(DB::table('larasell_product_product_image')->pluck('product_image_id')->all())->toBe([$other->id]);
});

it('takes the image out of its folder but keeps the folder', function () {
    $image = storedProductImage('apple.jpg');
    $other = storedProductImage('mango.jpg');
    $folder = Folder::create(['name' => 'Campaigns']);
    $folder->productImages()->attach([$image->id, $other->id]);

    app(DeleteProductImage::class)->handle($image);

    // Read from the link table itself: a join through the relation would hide a leftover link.
    expect(DB::table('larasell_admin_media_folder_product_image')->pluck('product_image_id')->all())->toBe([$other->id])
        ->and(Folder::query()->whereKey($folder->id)->exists())->toBeTrue();
});

it('deletes the thumbnail of the image and its file', function () {
    $image = storedProductImage('apple.jpg');
    $other = storedProductImage('mango.jpg');
    $disk = Storage::disk(config('larasell.images.disk'));
    $disk->put('thumbs/apple.webp', 'a');
    $disk->put('thumbs/mango.webp', 'm');
    ProductImageThumbnail::create(['product_image_id' => $image->id, 'path' => 'thumbs/apple.webp']);
    ProductImageThumbnail::create(['product_image_id' => $other->id, 'path' => 'thumbs/mango.webp']);

    app(DeleteProductImage::class)->handle($image);

    // Read from the table itself, as with the folder links.
    expect(DB::table('larasell_admin_media_product_image_thumbnails')->pluck('product_image_id')->all())->toBe([$other->id]);
    $disk->assertMissing('thumbs/apple.webp');
    $disk->assertExists('thumbs/mango.webp');
});
