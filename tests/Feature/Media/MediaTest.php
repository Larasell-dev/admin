<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Admin\Navigation;

function productImage(string $file): int
{
    return DB::table('larasell_product_images')->insertGetId(['file' => $file]);
}

it('lists folders before product images, each alphabetically', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    // Created out of order, with mixed case, and with a folder that sorts after every image by name.
    $zoo = Folder::create(['name' => 'Zoo']);
    $banners = Folder::create(['name' => 'banners']);
    $archive = Folder::create(['name' => 'Archive']);
    $zebra = productImage('products/zebra.jpg');
    $apple = productImage('products/Apple.png');
    $mango = productImage('products/mango.webp');

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/media')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('TablePage')
            ->where('table.title', 'Media')
            ->where('table.columns', [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'type', 'label' => 'Type', 'type' => 'text'],
            ])
            ->where('table.pagination.total', 6)
            ->where('table.rows', [
                ['id' => "folder-{$archive->id}", 'name' => 'Archive', 'type' => 'Folder', '_actions' => [], '_link' => null],
                ['id' => "folder-{$banners->id}", 'name' => 'banners', 'type' => 'Folder', '_actions' => [], '_link' => null],
                ['id' => "folder-{$zoo->id}", 'name' => 'Zoo', 'type' => 'Folder', '_actions' => [], '_link' => null],
                ['id' => "product-image-{$apple}", 'name' => 'Apple.png', 'type' => 'Product image', '_actions' => [], '_link' => null],
                ['id' => "product-image-{$mango}", 'name' => 'mango.webp', 'type' => 'Product image', '_actions' => [], '_link' => null],
                ['id' => "product-image-{$zebra}", 'name' => 'zebra.jpg', 'type' => 'Product image', '_actions' => [], '_link' => null],
            ]));
});

it('keeps folders first across pages', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    productImage('products/a.jpg');
    foreach (range(1, 25) as $number) {
        Folder::create(['name' => sprintf('Folder %02d', $number)]);
    }

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/media?page=2')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.pagination.total', 26)
            ->where('table.rows.0.name', 'a.jpg')
            ->count('table.rows', 1));
});

it('shows an empty state without folders or product images', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/media')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.rows', [])
            ->where('table.emptyState.title', 'No media yet'));
});

it('requires a signed-in admin', function () {
    $this->get('/admin/media')->assertRedirect('/admin/login');
});

it('is in the navigation by default', function () {
    expect(config('larasell.media.enabled'))->toBeTrue()
        ->and(array_column(Navigation::resolve()->tree(), 'id'))->toContain('larasell.admin.media.index');
});

it('leaves the admin panel when disabled', function () {
    $this->bootingWith(fn () => config(['larasell.media.enabled' => false]));
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    expect(array_column(Navigation::resolve()->tree(), 'id'))->not->toContain('larasell.admin.media.index');

    $this->actingAs($admin, 'larasell-admin')->get('/admin/media')->assertNotFound();
});
