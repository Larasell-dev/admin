<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;
use Larasell\Larasell\Translatable;

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
            ->component('TablePage')
            ->where('table.title', 'Products')
            ->where('table.columns', [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'stock', 'label' => 'Stock', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Visibility', 'type' => 'badge'],
            ])
            ->where('table.pagination.total', 2)
            ->where('table.rows.0.name', 'Boots')
            ->where('table.rows.0.stock', 'Not tracked')
            ->where('table.rows.0.status', 'Visible')
            ->where('table.rows.0._variants.status', 'success')
            ->where('table.rows.1.name', 'Socks')
            ->where('table.rows.1.stock', '12')
            ->where('table.rows.1.status', 'Hidden')
            ->where('table.rows.1._variants.status', 'neutral'));
});

it('shows a stock of zero as 0, not as untracked', function () {
    signInAdmin();

    Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000), 'stock' => 0]);

    $this->get('/admin/products')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('table.rows.0.stock', '0'));
});

it('says there are no products yet when the store has none', function () {
    signInAdmin();

    $this->get('/admin/products')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.pagination.total', 0)
            ->where('table.emptyState', [
                'title' => 'No products yet',
                'description' => 'Products you add to the store show up here.',
                'icon' => 'ShoppingBag',
                'actions' => [
                    ['label' => 'Add product', 'method' => 'get', 'url' => url('/admin/products/create'), 'icon' => 'Add'],
                ],
            ]));
});

it('links to adding a product from the header', function () {
    signInAdmin();

    $this->get('/admin/products')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('table.headerActions', [
            ['label' => 'Add product', 'method' => 'get', 'url' => url('/admin/products/create'), 'icon' => 'Add'],
        ]));
});

it('links each product to its edit page', function () {
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $this->get('/admin/products')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('table.rows.0._link', url("/admin/products/{$boots->id}/edit")));
});

it('shows the page for adding a product', function () {
    signInAdmin();

    $this->get('/admin/products/create')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Product/Create')
            ->where('storeUrl', url('/admin/products'))
            ->where('indexUrl', url('/admin/products')));
});

it('creates a product and opens it', function (Visibility $status) {
    signInAdmin();

    $response = $this->post('/admin/products', ['name' => 'Linen Shirt', 'slug' => 'linen-shirt', 'status' => $status->value]);

    $product = Product::query()->sole();

    $response->assertSessionHasNoErrors()
        ->assertRedirect("/admin/products/{$product->id}/edit");

    expect($product->name->all())->toBe(['en' => 'Linen Shirt'])
        ->and($product->slug->all())->toBe(['en' => 'linen-shirt'])
        ->and($product->status)->toBe($status)
        // The form has no price yet, and every product needs one.
        ->and($product->price->amount())->toBe('0');
})->with([
    'visible' => [Visibility::Visible],
    'hidden' => [Visibility::Hidden],
]);

it('shows the product\'s values to edit on its page', function () {
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000), 'status' => Visibility::Hidden]);

    $this->get("/admin/products/{$boots->id}/edit")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Product/Edit')
            ->where('product', ['id' => $boots->id, 'name' => 'Boots', 'slug' => 'boots', 'visible' => false])
            ->where('updateUrl', url("/admin/products/{$boots->id}"))
            ->where('indexUrl', url('/admin/products')));
});

it('returns 404 for editing a product that does not exist', function () {
    signInAdmin();

    $this->get('/admin/products/999/edit')->assertNotFound();
});

it('updates a product and stays on its page, keeping its price', function () {
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $this->put("/admin/products/{$boots->id}", ['name' => 'Rain Boots', 'slug' => 'rain-boots', 'status' => 'hidden'])
        ->assertSessionHasNoErrors()
        ->assertRedirect("/admin/products/{$boots->id}/edit");

    $boots->refresh();

    expect($boots->name->get())->toBe('Rain Boots')
        ->and($boots->slug->get())->toBe('rain-boots')
        ->and($boots->status)->toBe(Visibility::Hidden)
        ->and($boots->price->amount())->toBe('1000');
});

it('only changes the current locale\'s product name and slug', function () {
    signInAdmin();

    $boots = Product::create([
        'name' => ['en' => 'Boots', 'de' => 'Stiefel'],
        'slug' => ['en' => 'boots', 'de' => 'stiefel'],
        'price' => Price::of(1000),
    ]);

    $this->put("/admin/products/{$boots->id}", ['name' => 'Rain Boots', 'slug' => 'rain-boots', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    $boots->refresh();

    expect($boots->name->all())->toBe(['en' => 'Rain Boots', 'de' => 'Stiefel'])
        ->and($boots->slug->all())->toBe(['en' => 'rain-boots', 'de' => 'stiefel']);
});

it('rejects an invalid product', function (array $input, string $error) {
    signInAdmin();

    Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $this->post('/admin/products', [...['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible'], ...$input])
        ->assertSessionHasErrors($error);

    expect(Product::query()->count())->toBe(1);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'too long name' => [['name' => str_repeat('a', 256)], 'name'],
    'missing slug' => [['slug' => ''], 'slug'],
    'slug with spaces' => [['slug' => 'wool socks'], 'slug'],
    'slug with capitals' => [['slug' => 'Socks'], 'slug'],
    'slug of another product' => [['slug' => 'boots'], 'slug'],
    'missing visibility' => [['status' => null], 'status'],
    'unknown visibility' => [['status' => 'secret'], 'status'],
]);

it('lets a product keep its own slug, but not take another one\'s', function () {
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);
    Product::create(['slug' => 'socks', 'name' => 'Socks', 'price' => Price::of(500)]);

    $this->put("/admin/products/{$boots->id}", ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible'])
        ->assertSessionHasNoErrors();
    $this->put("/admin/products/{$boots->id}", ['name' => 'Boots', 'slug' => 'socks', 'status' => 'visible'])
        ->assertSessionHasErrors('slug');
});

it('does not let guests create or edit products', function () {
    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $this->get('/admin/products/create')->assertRedirect('/admin/login');
    $this->post('/admin/products', ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible'])->assertRedirect('/admin/login');
    $this->get("/admin/products/{$boots->id}/edit")->assertRedirect('/admin/login');
    $this->put("/admin/products/{$boots->id}", ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible'])->assertRedirect('/admin/login');

    expect(Product::query()->sole()->name->get())->toBe('Boots');
});

it('offers to delete each product', function () {
    signInAdmin();

    Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $this->get('/admin/products')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.rows.0._actions.0.label', 'Delete')
            ->where('table.rows.0._actions.0.method', 'post')
            ->where('table.rows.0._actions.0.destructive', true)
            ->where('table.rows.0._actions.0.icon', 'DeleteBin')
            ->where('table.rows.0._actions.0.confirm.title', 'Delete product'));
});

it('deletes a product with its variants and keeps the others', function () {
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);
    Product::create(['slug' => 'socks', 'name' => 'Socks', 'price' => Price::of(500)]);

    $delete = $this->get('/admin/products')->inertiaProps('table.rows.0._actions.0.url');

    $this->from('/admin/products')->post($delete)->assertRedirect('/admin/products');

    expect(Product::query()->pluck('slug')->map->get()->all())->toBe(['socks'])
        ->and(DB::table('larasell_product_variants')->where('product_id', $boots->id)->exists())->toBeFalse();
});

it('removes a deleted product from its categories and keeps the categories', function () {
    signInAdmin();

    $shoes = Category::create(['name' => Translatable::fromString('Shoes'), 'slug' => Translatable::fromString('shoes')]);
    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);
    $socks = Product::create(['slug' => 'socks', 'name' => 'Socks', 'price' => Price::of(500)]);
    $boots->categories()->attach($shoes);
    $socks->categories()->attach($shoes);

    $delete = $this->get('/admin/products')->inertiaProps('table.rows.0._actions.0.url');

    $this->from('/admin/products')->post($delete)->assertRedirect('/admin/products');

    expect(DB::table('larasell_category_product')->where('product_id', $boots->id)->exists())->toBeFalse()
        ->and($shoes->fresh()?->products()->pluck('larasell_products.id')->all())->toBe([$socks->id]);
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
    $this->get('/admin/products/create')->assertNotFound();
    $this->post('/admin/products')->assertNotFound();
    $this->get('/admin')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => ! collect($items)->pluck('label')->contains('Products')));
});
