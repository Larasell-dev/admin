<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Larasell\Models\ProductImage;

function destroyingAdmin(): Admin
{
    return Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
}

it('deletes a folder with everything in it and returns to the page', function () {
    $campaigns = Folder::create(['name' => 'Campaigns']);
    $summer = Folder::create(['name' => 'Summer', 'parent_id' => $campaigns->id]);
    Folder::create(['name' => 'Beach', 'parent_id' => $summer->id]);
    $brand = Folder::create(['name' => 'Brand']);

    $this->actingAs(destroyingAdmin(), 'larasell-admin')
        ->from("/admin/media/folders/{$campaigns->id}")
        ->delete("/admin/media/folders/{$summer->id}", ['confirmation' => 'Summer'])
        ->assertRedirect("/admin/media/folders/{$campaigns->id}");

    expect(Folder::query()->orderBy('id')->pluck('name')->all())->toBe(['Campaigns', 'Brand']);
});

it('deletes a product image and its file and returns to the page', function () {
    Storage::fake(config('larasell.images.disk'));
    $image = ProductImage::create(['file' => UploadedFile::fake()->image('apple.jpg')]);
    $other = ProductImage::create(['file' => UploadedFile::fake()->image('mango.jpg')]);

    $this->actingAs(destroyingAdmin(), 'larasell-admin')
        ->from('/admin/media')
        ->delete("/admin/media/product-images/{$image->id}", ['confirmation' => basename($image->file)])
        ->assertRedirect('/admin/media');

    expect(ProductImage::query()->pluck('id')->all())->toBe([$other->id]);
    Storage::disk(config('larasell.images.disk'))->assertMissing($image->file);
});

it('keeps a folder whose name was not typed to confirm', function (?string $typed) {
    $folder = Folder::create(['name' => 'Summer']);

    $this->actingAs(destroyingAdmin(), 'larasell-admin')
        ->from('/admin/media')
        ->delete("/admin/media/folders/{$folder->id}", ['confirmation' => $typed])
        ->assertRedirect('/admin/media')
        ->assertSessionHasErrors(['confirmation' => 'Type "Summer" to confirm.']);

    expect(Folder::query()->count())->toBe(1);
})->with(['missing' => null, 'wrong case' => 'summer', 'another name' => 'Winter']);

it('keeps a product image whose file name was not typed to confirm', function () {
    Storage::fake(config('larasell.images.disk'));
    $image = ProductImage::create(['file' => UploadedFile::fake()->image('apple.jpg')]);

    // The full path is not the name shown in the table, which is only the file's name.
    $this->actingAs(destroyingAdmin(), 'larasell-admin')
        ->from('/admin/media')
        ->delete("/admin/media/product-images/{$image->id}", ['confirmation' => $image->file])
        ->assertSessionHasErrors(['confirmation' => 'Type "'.basename($image->file).'" to confirm.']);

    expect(ProductImage::query()->count())->toBe(1);
    Storage::disk(config('larasell.images.disk'))->assertExists($image->file);
});

it('returns not found when deleting media that does not exist', function () {
    $this->actingAs(destroyingAdmin(), 'larasell-admin');

    $this->delete('/admin/media/folders/999')->assertNotFound();
    $this->delete('/admin/media/product-images/999')->assertNotFound();
});

it('requires a signed-in admin to delete media', function () {
    $folder = Folder::create(['name' => 'Campaigns']);

    $this->delete("/admin/media/folders/{$folder->id}")->assertRedirect('/admin/login');

    expect(Folder::query()->count())->toBe(1);
});
