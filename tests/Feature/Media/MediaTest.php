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

it('lists folders before product images, each alphabetically, linking folders to their page and images to their file', function () {
    config([
        'larasell.images.disk' => 'public',
        'filesystems.disks.public.url' => 'https://cdn.example.com/media',
    ]);
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
                ['id' => "folder-{$archive->id}", 'name' => 'Archive', 'type' => 'Folder', '_actions' => [], '_link' => url("/admin/media/folders/{$archive->id}"), '_images' => []],
                ['id' => "folder-{$banners->id}", 'name' => 'banners', 'type' => 'Folder', '_actions' => [], '_link' => url("/admin/media/folders/{$banners->id}"), '_images' => []],
                ['id' => "folder-{$zoo->id}", 'name' => 'Zoo', 'type' => 'Folder', '_actions' => [], '_link' => url("/admin/media/folders/{$zoo->id}"), '_images' => []],
                ['id' => "product-image-{$apple}", 'name' => 'Apple.png', 'type' => 'Product image', '_actions' => [], '_link' => 'https://cdn.example.com/media/products/Apple.png', '_images' => []],
                ['id' => "product-image-{$mango}", 'name' => 'mango.webp', 'type' => 'Product image', '_actions' => [], '_link' => 'https://cdn.example.com/media/products/mango.webp', '_images' => []],
                ['id' => "product-image-{$zebra}", 'name' => 'zebra.jpg', 'type' => 'Product image', '_actions' => [], '_link' => 'https://cdn.example.com/media/products/zebra.jpg', '_images' => []],
            ]));
});

it('lists only top-level folders and product images at the root', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $campaigns = Folder::create(['name' => 'Campaigns']);
    Folder::create(['name' => 'Summer', 'parent_id' => $campaigns->id]);
    productImage('products/apple.png');

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/media')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.pagination.total', 2)
            ->where('table.rows.0.name', 'Campaigns')
            ->where('table.rows.1.name', 'apple.png'));
});

it('lists only the folders directly inside a folder on its page', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $campaigns = Folder::create(['name' => 'Campaigns']);
    $winter = Folder::create(['name' => 'Winter', 'parent_id' => $campaigns->id]);
    $summer = Folder::create(['name' => 'Summer', 'parent_id' => $campaigns->id]);
    Folder::create(['name' => 'Beach', 'parent_id' => $summer->id]);
    Folder::create(['name' => 'Brand']);
    productImage('products/apple.png');

    $this->actingAs($admin, 'larasell-admin')
        ->get("/admin/media/folders/{$campaigns->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('TablePage')
            ->where('table.title', 'Campaigns')
            ->where('table.pagination.total', 2)
            ->where('table.rows', [
                ['id' => "folder-{$summer->id}", 'name' => 'Summer', 'type' => 'Folder', '_actions' => [], '_link' => url("/admin/media/folders/{$summer->id}"), '_images' => []],
                ['id' => "folder-{$winter->id}", 'name' => 'Winter', 'type' => 'Folder', '_actions' => [], '_link' => url("/admin/media/folders/{$winter->id}"), '_images' => []],
            ]));
});

it('shows the empty state for a folder without contents', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $campaigns = Folder::create(['name' => 'Campaigns']);
    Folder::create(['name' => 'Brand']);
    productImage('products/apple.png');

    $this->actingAs($admin, 'larasell-admin')
        ->get("/admin/media/folders/{$campaigns->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('table.rows', []));
});

it('returns not found for a folder that does not exist', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')->get('/admin/media/folders/999')->assertNotFound();
});

it('requires a signed-in admin for a folder page', function () {
    $campaigns = Folder::create(['name' => 'Campaigns']);

    $this->get("/admin/media/folders/{$campaigns->id}")->assertRedirect('/admin/login');
});

it('links each folder page back to its parent, and the root to nothing', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $campaigns = Folder::create(['name' => 'Campaigns']);
    $summer = Folder::create(['name' => 'Summer', 'parent_id' => $campaigns->id]);
    $this->actingAs($admin, 'larasell-admin');

    $this->get('/admin/media')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('table.backLink', null));

    $this->get("/admin/media/folders/{$campaigns->id}")->assertInertia(fn (AssertableInertia $page) => $page
        ->where('table.backLink', ['url' => url('/admin/media'), 'label' => 'Back to Media']));

    $this->get("/admin/media/folders/{$summer->id}")->assertInertia(fn (AssertableInertia $page) => $page
        ->where('table.backLink', ['url' => url("/admin/media/folders/{$campaigns->id}"), 'label' => 'Back to Campaigns']));
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

    $folder = Folder::create(['name' => 'Campaigns']);

    $this->actingAs($admin, 'larasell-admin')->get('/admin/media')->assertNotFound();
    $this->actingAs($admin, 'larasell-admin')->get("/admin/media/folders/{$folder->id}")->assertNotFound();
});
