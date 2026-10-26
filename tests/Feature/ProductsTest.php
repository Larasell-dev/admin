<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Larasell\Dimensions;
use Larasell\Larasell\Enums\LengthUnit;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Enums\WeightUnit;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;
use Larasell\Larasell\Translatable;
use Larasell\Larasell\Weight;

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
            ->where('product', [
                'id' => $boots->id,
                'name' => 'Boots',
                'slug' => 'boots',
                'visible' => false,
                'weight' => null,
                'dimensions' => null,
                'sku' => null,
                'barcode' => null,
                'stock' => null,
                'allowBackorders' => true,
                'minQuantity' => null,
                'maxQuantity' => null,
            ])
            ->where('updateUrl', url("/admin/products/{$boots->id}"))
            ->where('indexUrl', url('/admin/products')));
});

it('shows the product\'s weight and dimensions in the units they were saved in', function () {
    signInAdmin();

    $poster = Product::create([
        'slug' => 'poster',
        'name' => 'Poster',
        'price' => Price::of(1999),
        'weight' => Weight::of('12.5', 'lb'),
        'dimensions' => Dimensions::of('50', '35.5', '0', 'in'),
    ]);

    $this->get("/admin/products/{$poster->id}/edit")
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Strings, so decimals reach the form exactly as they were saved.
            ->where('product.weight', ['amount' => '12.5', 'unit' => 'lb'])
            ->where('product.dimensions', ['length' => '50', 'width' => '35.5', 'height' => '0', 'unit' => 'in']));
});

it('saves the weight and dimensions as entered, without converting them', function () {
    signInAdmin();

    $this->post('/admin/products', [
        'name' => 'Poster',
        'slug' => 'poster',
        'status' => 'visible',
        'weight' => ['amount' => '1500', 'unit' => 'g'],
        'dimensions' => ['length' => '50', 'width' => '35.5', 'height' => '0.2', 'unit' => 'mm'],
    ])->assertSessionHasNoErrors();

    $poster = Product::query()->sole();

    expect($poster->weight?->amount())->toBe('1500')
        ->and($poster->weight?->unit())->toBe(WeightUnit::Gram)
        ->and($poster->dimensions?->length()->amount())->toBe('50')
        ->and($poster->dimensions?->width()->amount())->toBe('35.5')
        ->and($poster->dimensions?->height()->amount())->toBe('0.2')
        ->and($poster->dimensions?->unit())->toBe(LengthUnit::Millimeter);
});

it('accepts a weight and dimensions of zero', function () {
    signInAdmin();

    $this->post('/admin/products', [
        'name' => 'E-book',
        'slug' => 'e-book',
        'status' => 'visible',
        'weight' => ['amount' => '0', 'unit' => 'kg'],
        'dimensions' => ['length' => '0', 'width' => '0', 'height' => '0', 'unit' => 'cm'],
    ])->assertSessionHasNoErrors();

    $book = Product::query()->sole();

    expect($book->weight?->amount())->toBe('0')
        ->and($book->dimensions?->volume())->toBe('0');
});

it('saves no weight or dimensions when their amounts are left empty, whatever the unit', function () {
    signInAdmin();

    $this->post('/admin/products', [
        'name' => 'E-book',
        'slug' => 'e-book',
        'status' => 'visible',
        'weight' => ['amount' => '', 'unit' => 'kg'],
        'dimensions' => ['length' => '', 'width' => '', 'height' => '', 'unit' => 'cm'],
    ])->assertSessionHasNoErrors();

    $book = Product::query()->sole();

    expect($book->weight)->toBeNull()
        ->and($book->dimensions)->toBeNull();
});

it('removes the weight and dimensions when they are cleared', function () {
    signInAdmin();

    $poster = Product::create([
        'slug' => 'poster',
        'name' => 'Poster',
        'price' => Price::of(1999),
        'weight' => Weight::of('2', 'kg'),
        'dimensions' => Dimensions::of('50', '35', '5', 'cm'),
    ]);

    $this->put("/admin/products/{$poster->id}", [
        'name' => 'Poster',
        'slug' => 'poster',
        'status' => 'visible',
        'weight' => ['amount' => '', 'unit' => 'kg'],
        'dimensions' => ['length' => '', 'width' => '', 'height' => '', 'unit' => 'cm'],
    ])->assertSessionHasNoErrors();

    $poster->refresh();

    expect($poster->weight)->toBeNull()
        ->and($poster->dimensions)->toBeNull();
});

it('rejects an invalid weight or dimensions', function (array $input, string $error) {
    signInAdmin();

    $this->post('/admin/products', [
        'name' => 'Poster',
        'slug' => 'poster',
        'status' => 'visible',
        'weight' => ['amount' => '2', 'unit' => 'kg'],
        'dimensions' => ['length' => '50', 'width' => '35', 'height' => '5', 'unit' => 'cm'],
        ...$input,
    ])->assertSessionHasErrors($error);

    expect(Product::query()->count())->toBe(0);
})->with([
    'negative weight' => [['weight' => ['amount' => '-1', 'unit' => 'kg']], 'weight.amount'],
    'weight that is not a number' => [['weight' => ['amount' => 'heavy', 'unit' => 'kg']], 'weight.amount'],
    'weight with a comma' => [['weight' => ['amount' => '1,5', 'unit' => 'kg']], 'weight.amount'],
    'weight in exponent notation' => [['weight' => ['amount' => '1e3', 'unit' => 'kg']], 'weight.amount'],
    'weight without a unit' => [['weight' => ['amount' => '2']], 'weight.unit'],
    'weight in an unknown unit' => [['weight' => ['amount' => '2', 'unit' => 'stone']], 'weight.unit'],
    'weight in a length unit' => [['weight' => ['amount' => '2', 'unit' => 'cm']], 'weight.unit'],
    'negative length' => [['dimensions' => ['length' => '-5', 'width' => '35', 'height' => '5', 'unit' => 'cm']], 'dimensions.length'],
    'width that is not a number' => [['dimensions' => ['length' => '50', 'width' => 'wide', 'height' => '5', 'unit' => 'cm']], 'dimensions.width'],
    'missing height' => [['dimensions' => ['length' => '50', 'width' => '35', 'height' => '', 'unit' => 'cm']], 'dimensions.height'],
    'only a length' => [['dimensions' => ['length' => '50', 'width' => '', 'height' => '', 'unit' => 'cm']], 'dimensions.width'],
    'dimensions without a unit' => [['dimensions' => ['length' => '50', 'width' => '35', 'height' => '5']], 'dimensions.unit'],
    'dimensions in a weight unit' => [['dimensions' => ['length' => '50', 'width' => '35', 'height' => '5', 'unit' => 'kg']], 'dimensions.unit'],
]);

it('explains invalid weights and dimensions in words, not field paths', function () {
    signInAdmin();

    $this->post('/admin/products', [
        'name' => 'Poster',
        'slug' => 'poster',
        'status' => 'visible',
        'weight' => ['amount' => '-1', 'unit' => 'kg'],
        'dimensions' => ['length' => '50', 'width' => 'wide', 'height' => '', 'unit' => 'cm'],
    ])->assertSessionHasErrors([
        'weight.amount' => 'The weight must be a number of 0 or more, with a dot for decimals.',
        'dimensions.width' => 'The width must be a number of 0 or more, with a dot for decimals.',
        'dimensions.height' => 'Enter a height too, or leave length, width and height empty.',
    ]);
});

it('shows the product\'s SKU and barcode on its page', function () {
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000), 'sku' => 'BOOT-42', 'barcode' => '4006381333931']);

    $this->get("/admin/products/{$boots->id}/edit")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.sku', 'BOOT-42')
            ->where('product.barcode', '4006381333931'));
});

it('saves the SKU as entered and the barcode as its digits', function () {
    signInAdmin();

    $this->post('/admin/products', [
        'name' => 'Boots',
        'slug' => 'boots',
        'status' => 'visible',
        'sku' => 'Boot/42-black',
        'barcode' => '400-6381 333931',
    ])->assertSessionHasNoErrors();

    $boots = Product::query()->sole();

    expect($boots->sku)->toBe('Boot/42-black')
        ->and($boots->barcode)->toBe('4006381333931');
});

it('saves no SKU or barcode when they are left empty', function () {
    signInAdmin();

    $this->post('/admin/products', ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible', 'sku' => '', 'barcode' => ''])
        ->assertSessionHasNoErrors();

    $boots = Product::query()->sole();

    expect($boots->sku)->toBeNull()
        ->and($boots->barcode)->toBeNull();
});

it('changes and removes the SKU and barcode, and keeps them when they are unchanged', function () {
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000), 'sku' => 'BOOT-42', 'barcode' => '4006381333931']);
    $update = fn (array $input) => $this->put("/admin/products/{$boots->id}", ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible', ...$input]);

    // Its own SKU and barcode aren't taken by another product.
    $update(['sku' => 'BOOT-42', 'barcode' => '4006381333931'])->assertSessionHasNoErrors();
    $update(['sku' => 'BOOT-43', 'barcode' => '012345678905'])->assertSessionHasNoErrors();
    expect($boots->refresh()->sku)->toBe('BOOT-43')
        ->and($boots->barcode)->toBe('012345678905');

    $update(['sku' => '', 'barcode' => ''])->assertSessionHasNoErrors();
    expect($boots->refresh()->sku)->toBeNull()
        ->and($boots->barcode)->toBeNull();
});

it('rejects an invalid or taken SKU or barcode', function (array $input, string $error) {
    signInAdmin();

    Product::create(['slug' => 'shirt', 'name' => 'Shirt', 'price' => Price::of(1000), 'sku' => 'SHIRT-1', 'barcode' => '4006381333931']);

    $this->post('/admin/products', ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible', ...$input])
        ->assertSessionHasErrors($error);

    expect(Product::query()->count())->toBe(1);
})->with([
    'SKU of another product' => [['sku' => 'SHIRT-1'], 'sku'],
    'SKU over 255 characters' => [['sku' => str_repeat('a', 256)], 'sku'],
    'barcode of another product' => [['barcode' => '4006381333931'], 'barcode'],
    'barcode of another product, written with dashes' => [['barcode' => '400-6381-333931'], 'barcode'],
    'barcode with letters' => [['barcode' => '40063813339A1'], 'barcode'],
    'barcode of the wrong length' => [['barcode' => '1234567890'], 'barcode'],
    'barcode with a wrong check digit' => [['barcode' => '4006381333932'], 'barcode'],
]);

it('explains why a barcode is invalid', function (string $barcode, string $message) {
    signInAdmin();

    $this->post('/admin/products', ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible', 'barcode' => $barcode])
        ->assertSessionHasErrors(['barcode' => $message]);
})->with([
    'letters' => ['40063813339A1', 'A barcode must contain only digits.'],
    'wrong length' => ['1234567890', 'A barcode must be an EAN-8, UPC-A, EAN-13, or GTIN-14.'],
    'wrong check digit' => ['4006381333932', 'A barcode must have a valid check digit.'],
]);

it('shows the product\'s stock, backorders and quantity limits on its page', function () {
    signInAdmin();

    $socks = Product::create([
        'slug' => 'socks',
        'name' => 'Socks',
        'price' => Price::of(300),
        'stock' => 500,
        'allow_backorders' => false,
        'min_quantity' => 6,
        'max_quantity' => 24,
    ]);

    $this->get("/admin/products/{$socks->id}/edit")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.stock', 500)
            ->where('product.allowBackorders', false)
            ->where('product.minQuantity', 6)
            ->where('product.maxQuantity', 24));
});

it('saves the stock, backorders and quantity limits', function () {
    signInAdmin();

    $this->post('/admin/products', [
        'name' => 'Socks',
        'slug' => 'socks',
        'status' => 'visible',
        'stock' => '500',
        'allow_backorders' => '0',
        'min_quantity' => '6',
        'max_quantity' => '6',
    ])->assertSessionHasNoErrors();

    $socks = Product::query()->sole();
    $variant = $socks->defaultVariant();

    expect($socks->stock)->toBe(500)
        ->and($socks->allow_backorders)->toBeFalse()
        ->and($socks->min_quantity)->toBe(6)
        ->and($socks->max_quantity)->toBe(6)
        ->and($variant->availableStock())->toBe(500)
        ->and($variant->allowsBackorders())->toBeFalse();
});

it('leaves the stock untracked and the quantity unlimited when they are left empty', function () {
    signInAdmin();

    $this->post('/admin/products', [
        'name' => 'E-book',
        'slug' => 'e-book',
        'status' => 'visible',
        'stock' => '',
        'allow_backorders' => '1',
        'min_quantity' => '',
        'max_quantity' => '',
    ])->assertSessionHasNoErrors();

    $book = Product::query()->sole();

    expect($book->stock)->toBeNull()
        ->and($book->allow_backorders)->toBeTrue()
        ->and($book->min_quantity)->toBeNull()
        ->and($book->max_quantity)->toBeNull();
});

it('accepts a stock of zero and below, which backorders lead to', function (string $stock, int $saved) {
    signInAdmin();

    $this->post('/admin/products', ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible', 'stock' => $stock])
        ->assertSessionHasNoErrors();

    expect(Product::query()->sole()->stock)->toBe($saved);
})->with([
    'zero' => ['0', 0],
    'negative' => ['-3', -3],
]);

it('changes the stock, backorders and quantity limits of a product', function () {
    signInAdmin();

    $socks = Product::create([
        'slug' => 'socks',
        'name' => 'Socks',
        'price' => Price::of(300),
        'stock' => 500,
        'allow_backorders' => false,
        'min_quantity' => 5,
        'max_quantity' => 10,
    ]);
    $update = fn (array $input) => $this->put("/admin/products/{$socks->id}", ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible', ...$input]);

    // A new minimum above the old maximum, then a new maximum below the old minimum.
    $update(['stock' => '12', 'allow_backorders' => '1', 'min_quantity' => '20', 'max_quantity' => '30'])->assertSessionHasNoErrors();
    expect($socks->refresh()->only(['stock', 'allow_backorders', 'min_quantity', 'max_quantity']))
        ->toBe(['stock' => 12, 'allow_backorders' => true, 'min_quantity' => 20, 'max_quantity' => 30]);

    $update(['stock' => '12', 'allow_backorders' => '1', 'min_quantity' => '1', 'max_quantity' => '3'])->assertSessionHasNoErrors();
    expect($socks->refresh()->only(['min_quantity', 'max_quantity']))->toBe(['min_quantity' => 1, 'max_quantity' => 3]);

    $update(['stock' => '', 'allow_backorders' => '0', 'min_quantity' => '', 'max_quantity' => ''])->assertSessionHasNoErrors();
    expect($socks->refresh()->only(['stock', 'allow_backorders', 'min_quantity', 'max_quantity']))
        ->toBe(['stock' => null, 'allow_backorders' => false, 'min_quantity' => null, 'max_quantity' => null]);
});

it('rejects an invalid stock, backorders or quantity limit', function (array $input, string $error) {
    signInAdmin();

    $this->post('/admin/products', ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible', ...$input])
        ->assertSessionHasErrors($error);

    expect(Product::query()->count())->toBe(0);
})->with([
    'stock with decimals' => [['stock' => '1.5'], 'stock'],
    'stock that is not a number' => [['stock' => 'many'], 'stock'],
    'backorders that are neither on nor off' => [['allow_backorders' => 'maybe'], 'allow_backorders'],
    'minimum quantity of zero' => [['min_quantity' => '0'], 'min_quantity'],
    'maximum quantity of zero' => [['max_quantity' => '0'], 'max_quantity'],
    'minimum quantity with decimals' => [['min_quantity' => '2.5'], 'min_quantity'],
    'maximum below the minimum' => [['min_quantity' => '10', 'max_quantity' => '7'], 'max_quantity'],
]);

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
