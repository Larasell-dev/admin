<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Translatable;

function category(string $name, ?Category $parent = null, Visibility $status = Visibility::Visible): Category
{
    return Category::create([
        'parent_id' => $parent?->id,
        'slug' => Translatable::fromString(str($name)->slug()->value()),
        'name' => Translatable::fromString($name),
        'status' => $status,
    ]);
}

it('redirects the index to the top-most root category', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    // The lowest id belongs to a child, so the first category overall is not the target.
    $shirts = category('Shirts');
    $clothing = category('Clothing');
    $shirts->update(['parent_id' => $clothing->id]);
    category('Shoes');

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/categories')
        ->assertRedirect("/admin/categories/{$clothing->id}");
});

it('shows the index when the store has no categories', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/categories')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Categories', shouldExist: false)
            ->where('categories', []));
});

it('shows every category of the store as a tree', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $clothing = category('Clothing');
    $shoes = category('Shoes');
    $shirts = category('Shirts', $clothing);
    category('Linen', $shirts, Visibility::Hidden);
    category('Boots', $shoes);

    $this->actingAs($admin, 'larasell-admin')
        ->get("/admin/categories/{$shoes->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Category', shouldExist: false)
            ->has('categories', 2)
            ->where('categories.0.name', 'Clothing')
            ->where('categories.0.children.0.name', 'Shirts')
            ->where('categories.0.children.0.children.0.name', 'Linen')
            ->where('categories.0.children.0.children.0.visible', false)
            ->where('categories.0.children.0.children.0.children', [])
            ->where('categories.1.name', 'Shoes')
            ->where('categories.1.visible', true)
            ->has('categories.1.children', 1)
            ->where('categories.1.children.0.name', 'Boots'));
});

it('links every category in the tree to its page', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $clothing = category('Clothing');
    $shirts = category('Shirts', $clothing);

    $this->actingAs($admin, 'larasell-admin')
        ->get("/admin/categories/{$shirts->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.0.url', url("/admin/categories/{$clothing->id}"))
            ->where('categories.0.children.0.url', url("/admin/categories/{$shirts->id}")));
});

it('shows a category, including a hidden one, next to the tree', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $clothing = category('Clothing');
    $linen = category('Linen', $clothing, Visibility::Hidden);

    $this->actingAs($admin, 'larasell-admin')
        ->get("/admin/categories/{$linen->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Category', shouldExist: false)
            ->where('category.id', $linen->id)
            ->where('category.name', 'Linen')
            ->where('categories.0.children.0.name', 'Linen'));
});

it('returns 404 for a category that does not exist', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/categories/999')
        ->assertNotFound();
});

it('hides a category and goes back to the previous page', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $shirts = category('Shirts', $clothing);

    $this->actingAs($admin, 'larasell-admin')
        ->from("/admin/categories/{$clothing->id}")
        ->post("/admin/categories/{$shirts->id}/hide")
        ->assertRedirect("/admin/categories/{$clothing->id}");

    expect($shirts->fresh()->status)->toBe(Visibility::Hidden)
        ->and($clothing->fresh()->status)->toBe(Visibility::Visible);
});

it('makes a hidden category visible again', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $sale = category('Sale', status: Visibility::Hidden);

    $this->actingAs($admin, 'larasell-admin')
        ->from("/admin/categories/{$sale->id}")
        ->post("/admin/categories/{$sale->id}/unhide")
        ->assertRedirect("/admin/categories/{$sale->id}");

    expect($sale->fresh()->status)->toBe(Visibility::Visible);
});

it('gives every category in the tree its hide and unhide urls', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');

    $this->actingAs($admin, 'larasell-admin')
        ->get("/admin/categories/{$clothing->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.0.hideUrl', url("/admin/categories/{$clothing->id}/hide"))
            ->where('categories.0.unhideUrl', url("/admin/categories/{$clothing->id}/unhide")));
});

it('does not let guests change a category\'s visibility', function (string $action) {
    $clothing = category('Clothing');

    $this->post("/admin/categories/{$clothing->id}/{$action}")
        ->assertRedirect('/admin/login');

    expect($clothing->fresh()->status)->toBe(Visibility::Visible);
})->with(['hide', 'unhide']);
