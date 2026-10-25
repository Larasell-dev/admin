<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;

function signInAdmin(): Admin
{
    $admin = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    test()->actingAs($admin, 'larasell-admin');

    return $admin;
}

it('lists the products by name, stock, and a visibility badge', function () {
    signInAdmin();

    Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000), 'status' => Visibility::Visible]);
    Product::create(['slug' => 'socks', 'name' => 'Socks', 'price' => Price::of(500), 'stock' => 12, 'status' => Visibility::Hidden]);

    $this->get('/admin/products')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Product/Index')
            ->where('products.title', 'Products')
            ->where('products.columns', [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'stock', 'label' => 'Stock', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Visibility', 'type' => 'badge'],
            ])
            ->where('products.pagination.total', 2)
            ->where('products.rows.0.name', 'Boots')
            ->where('products.rows.0.stock', 'Not tracked')
            ->where('products.rows.0.status', 'Visible')
            ->where('products.rows.0._variants.status', 'success')
            ->where('products.rows.1.name', 'Socks')
            ->where('products.rows.1.stock', '12')
            ->where('products.rows.1.status', 'Hidden')
            ->where('products.rows.1._variants.status', 'neutral'));
});

it('shows a stock of zero as 0, not as untracked', function () {
    signInAdmin();

    Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000), 'stock' => 0]);

    $this->get('/admin/products')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('products.rows.0.stock', '0'));
});

it('redirects guests away from the products', function () {
    $this->get('/admin/products')->assertRedirect('/admin/login');
});

it('shows Products in the navigation between Categories and Customers', function () {
    signInAdmin();

    $this->get('/admin')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => collect($items)->pluck('label')->all() === ['Home', 'Categories', 'Products', 'Customers', 'Settings']));
});

it('turns the products off when disabled in the config', function () {
    $this->bootingWith(fn () => config(['larasell.admin.products.enabled' => false]));
    signInAdmin();

    expect(Route::has('larasell.admin.products.index'))->toBeFalse();

    $this->get('/admin/products')->assertNotFound();
    $this->get('/admin')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => ! collect($items)->pluck('label')->contains('Products')));
});
