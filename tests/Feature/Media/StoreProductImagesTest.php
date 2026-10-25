<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Larasell\Models\ProductImage;

beforeEach(function () {
    Storage::fake(config('larasell.images.disk'));
});

function uploadingAdmin(): Admin
{
    return Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
}

it('uploads several product images at once and returns to the page', function () {
    $this->actingAs(uploadingAdmin(), 'larasell-admin')
        ->from('/admin/media')
        ->post('/admin/media/product-images', ['files' => [
            UploadedFile::fake()->image('apple.jpg'),
            UploadedFile::fake()->image('mango.png'),
        ]])
        ->assertRedirect('/admin/media')
        ->assertSessionHasNoErrors();

    $images = ProductImage::query()->orderBy('id')->get();
    expect($images)->toHaveCount(2);
    $images->each(fn (ProductImage $image) => Storage::disk(config('larasell.images.disk'))->assertExists($image->file));
    expect($images->pluck('file')->map(fn (string $file) => pathinfo($file, PATHINFO_EXTENSION))->all())->toBe(['jpg', 'png']);
});

it('uploads product images into the given folder', function () {
    $other = Folder::create(['name' => 'Other']);
    $campaigns = Folder::create(['name' => 'Campaigns']);

    $this->actingAs(uploadingAdmin(), 'larasell-admin')
        ->from("/admin/media/folders/{$campaigns->id}")
        ->post('/admin/media/product-images', ['folder_id' => $campaigns->id, 'files' => [
            UploadedFile::fake()->image('apple.jpg'),
            UploadedFile::fake()->image('mango.png'),
        ]])
        ->assertRedirect("/admin/media/folders/{$campaigns->id}")
        ->assertSessionHasNoErrors();

    expect($campaigns->productImages()->count())->toBe(2)
        ->and($other->productImages()->count())->toBe(0);
});

it('uploads nothing into a folder that does not exist', function () {
    $this->actingAs(uploadingAdmin(), 'larasell-admin')
        ->post('/admin/media/product-images', ['folder_id' => 999, 'files' => [UploadedFile::fake()->image('apple.jpg')]])
        ->assertSessionHasErrors('folder_id');

    expect(ProductImage::query()->count())->toBe(0)
        ->and(Storage::disk(config('larasell.images.disk'))->allFiles())->toBe([]);
});

it('uploads a single product image', function () {
    $this->actingAs(uploadingAdmin(), 'larasell-admin')
        ->post('/admin/media/product-images', ['files' => [UploadedFile::fake()->image('apple.webp')]])
        ->assertSessionHasNoErrors();

    expect(ProductImage::query()->count())->toBe(1);
});

it('requires at least one file', function (mixed $files) {
    $this->actingAs(uploadingAdmin(), 'larasell-admin')
        ->from('/admin/media')
        ->post('/admin/media/product-images', ['files' => $files])
        ->assertRedirect('/admin/media')
        ->assertSessionHasErrors('files');

    expect(ProductImage::query()->count())->toBe(0);
})->with(['missing' => null, 'empty' => [[]]]);

it('uploads nothing when one of the files is not an image', function (UploadedFile $invalid) {
    $this->actingAs(uploadingAdmin(), 'larasell-admin')
        ->from('/admin/media')
        ->post('/admin/media/product-images', ['files' => [UploadedFile::fake()->image('apple.jpg'), $invalid]])
        ->assertRedirect('/admin/media')
        ->assertSessionHasErrors('files.1');

    expect(ProductImage::query()->count())->toBe(0);
    expect(Storage::disk(config('larasell.images.disk'))->allFiles())->toBe([]);
})->with([
    'a document' => fn () => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
    'an svg' => fn () => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
    'too large' => fn () => UploadedFile::fake()->image('huge.jpg')->size(10 * 1024 + 1),
]);

it('requires a signed-in admin to upload', function () {
    $this->post('/admin/media/product-images', ['files' => [UploadedFile::fake()->image('apple.jpg')]])
        ->assertRedirect('/admin/login');

    expect(ProductImage::query()->count())->toBe(0);
});

it('tells the media page where to upload', function () {
    $this->actingAs(uploadingAdmin(), 'larasell-admin')
        ->get('/admin/media')
        ->assertInertia(fn ($page) => $page->where('uploadUrl', url('/admin/media/product-images')));
});

it('names the rejected file in its error', function () {
    $this->actingAs(uploadingAdmin(), 'larasell-admin')
        ->post('/admin/media/product-images', ['files' => [
            UploadedFile::fake()->image('apple.jpg'),
            UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->image('huge.jpg')->size(10 * 1024 + 1),
        ]])
        ->assertSessionHasErrors([
            'files.1' => '"notes.pdf" isn\'t an image.',
            'files.2' => '"huge.jpg" is larger than 10 MB.',
        ]);
});
