<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Larasell\Dimensions;
use Larasell\Larasell\Enums\LengthUnit;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Enums\WeightUnit;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Models\ProductImage;
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

    $this->get(route('larasell.admin.products.index'))
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

    $this->get(route('larasell.admin.products.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('table.rows.0.stock', '0'));
});

it('says there are no products yet when the store has none', function () {
    signInAdmin();

    $this->get(route('larasell.admin.products.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.pagination.total', 0)
            ->where('table.emptyState', [
                'title' => 'No products yet',
                'description' => 'Products you add to the store show up here.',
                'icon' => 'ShoppingBag',
                'actions' => [
                    ['label' => 'Add product', 'method' => 'get', 'url' => route('larasell.admin.products.create'), 'icon' => 'Add'],
                ],
            ]));
});

it('links to adding a product from the header', function () {
    signInAdmin();

    $this->get(route('larasell.admin.products.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('table.headerActions', [
            ['label' => 'Add product', 'method' => 'get', 'url' => route('larasell.admin.products.create'), 'icon' => 'Add'],
        ]));
});

it('links each product to its edit page', function () {
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $this->get(route('larasell.admin.products.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('table.rows.0._link', route('larasell.admin.products.edit', $boots)));
});

it('shows the page for adding a product', function () {
    signInAdmin();

    $this->get(route('larasell.admin.products.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Product/Create')
            ->where('storeUrl', route('larasell.admin.products.store'))
            ->where('indexUrl', route('larasell.admin.products.index'))
            ->where('uploadImagesUrl', route('larasell.admin.products.images.store')));
});

it('creates a product and opens it', function (Visibility $status) {
    signInAdmin();

    $response = $this->post(route('larasell.admin.products.store'), ['name' => 'Linen Shirt', 'slug' => 'linen-shirt', 'status' => $status->value]);

    $product = Product::query()->sole();

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('larasell.admin.products.edit', $product));

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

    $this->get(route('larasell.admin.products.edit', $boots))
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
                'categoryIds' => [],
                'images' => [],
            ])
            ->where('updateUrl', route('larasell.admin.products.update', $boots))
            ->where('indexUrl', route('larasell.admin.products.index'))
            ->where('uploadImagesUrl', route('larasell.admin.products.images.store')));
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

    $this->get(route('larasell.admin.products.edit', $poster))
        ->assertInertia(fn (AssertableInertia $page) => $page
            // Strings, so decimals reach the form exactly as they were saved.
            ->where('product.weight', ['amount' => '12.5', 'unit' => 'lb'])
            ->where('product.dimensions', ['length' => '50', 'width' => '35.5', 'height' => '0', 'unit' => 'in']));
});

it('saves the weight and dimensions as entered, without converting them', function () {
    signInAdmin();

    $this->post(route('larasell.admin.products.store'), [
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

    $this->post(route('larasell.admin.products.store'), [
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

    $this->post(route('larasell.admin.products.store'), [
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

    $this->put(route('larasell.admin.products.update', $poster), [
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

    $this->post(route('larasell.admin.products.store'), [
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

    $this->post(route('larasell.admin.products.store'), [
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

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.sku', 'BOOT-42')
            ->where('product.barcode', '4006381333931'));
});

it('saves the SKU as entered and the barcode as its digits', function () {
    signInAdmin();

    $this->post(route('larasell.admin.products.store'), [
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

    $this->post(route('larasell.admin.products.store'), ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible', 'sku' => '', 'barcode' => ''])
        ->assertSessionHasNoErrors();

    $boots = Product::query()->sole();

    expect($boots->sku)->toBeNull()
        ->and($boots->barcode)->toBeNull();
});

it('changes and removes the SKU and barcode, and keeps them when they are unchanged', function () {
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000), 'sku' => 'BOOT-42', 'barcode' => '4006381333931']);
    $update = fn (array $input) => $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible', ...$input]);

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

    $this->post(route('larasell.admin.products.store'), ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible', ...$input])
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

    $this->post(route('larasell.admin.products.store'), ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible', 'barcode' => $barcode])
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

    $this->get(route('larasell.admin.products.edit', $socks))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.stock', 500)
            ->where('product.allowBackorders', false)
            ->where('product.minQuantity', 6)
            ->where('product.maxQuantity', 24));
});

it('saves the stock, backorders and quantity limits', function () {
    signInAdmin();

    $this->post(route('larasell.admin.products.store'), [
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

    $this->post(route('larasell.admin.products.store'), [
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

    $this->post(route('larasell.admin.products.store'), ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible', 'stock' => $stock])
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
    $update = fn (array $input) => $this->put(route('larasell.admin.products.update', $socks), ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible', ...$input]);

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

    $this->post(route('larasell.admin.products.store'), ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible', ...$input])
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

it('offers the store\'s categories as a tree when adding or editing a product', function () {
    signInAdmin();

    $clothing = Category::create(['name' => 'Clothing', 'slug' => 'clothing']);
    $shirts = Category::create(['name' => 'Shirts', 'slug' => 'shirts', 'parent_id' => $clothing->id]);
    $shoes = Category::create(['name' => 'Shoes', 'slug' => 'shoes']);
    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $tree = fn (AssertableInertia $page) => $page
        ->has('categories', 2)
        ->where('categories.0.id', $clothing->id)
        ->where('categories.0.name', 'Clothing')
        ->where('categories.0.children.0.id', $shirts->id)
        ->where('categories.0.children.0.name', 'Shirts')
        ->where('categories.1.id', $shoes->id);

    $this->get(route('larasell.admin.products.create'))->assertInertia($tree);
    $this->get(route('larasell.admin.products.edit', $boots))->assertInertia($tree);
});

it('links to creating a category from adding or editing a product', function () {
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);
    $link = fn (AssertableInertia $page) => $page->where('createCategoryUrl', route('larasell.admin.categories.create'));

    $this->get(route('larasell.admin.products.create'))->assertInertia($link);
    $this->get(route('larasell.admin.products.edit', $boots))->assertInertia($link);
});

it('shows the categories a product is in on its page', function () {
    signInAdmin();

    $clothing = Category::create(['name' => 'Clothing', 'slug' => 'clothing']);
    $shoes = Category::create(['name' => 'Shoes', 'slug' => 'shoes']);
    Category::create(['name' => 'Socks', 'slug' => 'socks']);
    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);
    $boots->categories()->attach([$shoes->id, $clothing->id]);

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.categoryIds', [$clothing->id, $shoes->id]));
});

it('adds a new product to the selected categories', function () {
    signInAdmin();

    $clothing = Category::create(['name' => 'Clothing', 'slug' => 'clothing']);
    $shoes = Category::create(['name' => 'Shoes', 'slug' => 'shoes']);
    Category::create(['name' => 'Socks', 'slug' => 'socks']);

    $this->post(route('larasell.admin.products.store'), [
        'name' => 'Boots',
        'slug' => 'boots',
        'status' => 'visible',
        'categories' => [(string) $clothing->id, (string) $shoes->id],
    ])->assertSessionHasNoErrors();

    expect(Product::query()->sole()->categories->modelKeys())
        ->toBe([$clothing->id, $shoes->id]);
});

it('moves a product into the selected categories, and out of all when none are selected', function () {
    signInAdmin();

    $clothing = Category::create(['name' => 'Clothing', 'slug' => 'clothing']);
    $shoes = Category::create(['name' => 'Shoes', 'slug' => 'shoes']);
    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);
    $boots->categories()->attach($clothing);
    $valid = ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible'];

    $this->put(route('larasell.admin.products.update', $boots), [...$valid, 'categories' => [(string) $shoes->id]])
        ->assertSessionHasNoErrors();

    expect($boots->categories()->get()->modelKeys())->toBe([$shoes->id]);

    // The form leaves the field out when no category is selected.
    $this->put(route('larasell.admin.products.update', $boots), $valid)->assertSessionHasNoErrors();

    expect($boots->categories()->exists())->toBeFalse()
        ->and(Category::query()->count())->toBe(2);
});

it('rejects categories that do not exist', function (mixed $categories, string $error) {
    signInAdmin();

    $this->post(route('larasell.admin.products.store'), ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible', 'categories' => $categories])
        ->assertSessionHasErrors($error);

    expect(Product::query()->exists())->toBeFalse();
})->with([
    'not a list' => ['shoes', 'categories'],
    'missing category' => [['999'], 'categories.0'],
    'not an id' => [['shoes'], 'categories.0'],
]);

it('shows the product\'s images on its page in their order, whatever order they were added in', function () {
    Storage::fake('public');
    config(['larasell.images.disk' => 'public']);
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);
    $side = ProductImage::create(['file' => UploadedFile::fake()->image('side.png')]);
    $front = ProductImage::create(['file' => UploadedFile::fake()->image('front.png'), 'alt' => 'Boots from the front']);
    $boots->images()->attach([$side->id => ['position' => 1], $front->id => ['position' => 0]]);

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.images', [
                // Named after the stored file, which is what opening it shows.
                ['id' => $front->id, 'url' => $front->url(), 'filename' => basename($front->file), 'alt' => 'Boots from the front'],
                ['id' => $side->id, 'url' => $side->url(), 'filename' => basename($side->file), 'alt' => null],
            ]));
});

it('uploads an image without linking it to a product', function () {
    Storage::fake('public');
    config(['larasell.images.disk' => 'public']);
    signInAdmin();

    $response = $this->postJson(route('larasell.admin.products.images.store'), [
        'image' => UploadedFile::fake()->image('back.webp'),
    ])->assertCreated();

    $image = ProductImage::query()->sole();

    // The file is stored under a new name, but keeps its type.
    expect(pathinfo($image->file, PATHINFO_EXTENSION))->toBe('webp')
        // Saving a product links it; uploading doesn't.
        ->and($image->products()->exists())->toBeFalse();

    $response->assertExactJson(['image' => ['id' => $image->id, 'url' => $image->url(), 'filename' => basename($image->file), 'alt' => null]]);
    Storage::disk('public')->assertExists($image->file);
});

it('links the submitted images to a product in their order, the first as its thumbnail', function () {
    Storage::fake('public');
    config(['larasell.images.disk' => 'public']);
    signInAdmin();

    [$front, $side, $back] = collect(['front.png', 'side.png', 'back.png'])
        ->map(fn (string $name) => ProductImage::create(['file' => UploadedFile::fake()->image($name)]));
    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);
    $boots->images()->attach($front, ['position' => 0]);
    $valid = ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible'];

    // Out of id order, and the existing image moves from first to second.
    $this->put(route('larasell.admin.products.update', $boots), [...$valid, 'images' => [(string) $back->id, (string) $front->id, (string) $side->id]])
        ->assertSessionHasNoErrors();

    expect($boots->images()->get()->modelKeys())->toBe([$back->id, $front->id, $side->id])
        ->and($boots->fresh()?->thumbnail?->id)->toBe($back->id);

    // The form leaves the field out when the product has no images.
    $this->put(route('larasell.admin.products.update', $boots), $valid)->assertSessionHasNoErrors();

    // Removing images only unlinks them; the images and their files stay.
    expect($boots->images()->exists())->toBeFalse()
        ->and(ProductImage::query()->count())->toBe(3);
    Storage::disk('public')->assertExists([$front->file, $side->file, $back->file]);
});

it('links the submitted images to a new product in their order', function () {
    Storage::fake('public');
    config(['larasell.images.disk' => 'public']);
    signInAdmin();

    $front = ProductImage::create(['file' => UploadedFile::fake()->image('front.png')]);
    $side = ProductImage::create(['file' => UploadedFile::fake()->image('side.png')]);

    $this->post(route('larasell.admin.products.store'), ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible', 'images' => [(string) $side->id, (string) $front->id]])
        ->assertSessionHasNoErrors();

    expect(Product::query()->sole()->images->modelKeys())->toBe([$side->id, $front->id]);
});

it('saves the alt text of the product\'s images', function () {
    Storage::fake('public');
    config(['larasell.images.disk' => 'public']);
    signInAdmin();

    [$front, $side, $other] = collect(['front.png', 'side.png', 'other.png'])
        ->map(fn (string $name) => ProductImage::create(['file' => UploadedFile::fake()->image($name), 'alt' => "Old {$name}"]));
    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $this->put(route('larasell.admin.products.update', $boots), [
        'name' => 'Boots',
        'slug' => 'boots',
        'status' => 'visible',
        'images' => [(string) $front->id, (string) $side->id],
        // An emptied alt text is removed; one for an image the product doesn't have is ignored.
        'image_alts' => [$front->id => 'Boots from the front', $side->id => '', $other->id => 'Hijacked'],
    ])->assertSessionHasNoErrors();

    expect($front->fresh()?->alt)->toBe('Boots from the front')
        ->and($side->fresh()?->alt)->toBeNull()
        ->and($other->fresh()?->alt)->toBe('Old other.png');
});

it('saves the alt text of a new product\'s images', function () {
    Storage::fake('public');
    config(['larasell.images.disk' => 'public']);
    signInAdmin();

    $front = ProductImage::create(['file' => UploadedFile::fake()->image('front.png')]);

    $this->post(route('larasell.admin.products.store'), [
        'name' => 'Boots',
        'slug' => 'boots',
        'status' => 'visible',
        'images' => [(string) $front->id],
        'image_alts' => [$front->id => 'Boots from the front'],
    ])->assertSessionHasNoErrors();

    expect($front->fresh()?->alt)->toBe('Boots from the front');
});

it('rejects alt text longer than the column holds', function () {
    Storage::fake('public');
    config(['larasell.images.disk' => 'public']);
    signInAdmin();

    $front = ProductImage::create(['file' => UploadedFile::fake()->image('front.png')]);

    $this->post(route('larasell.admin.products.store'), [
        'name' => 'Boots',
        'slug' => 'boots',
        'status' => 'visible',
        'images' => [(string) $front->id],
        'image_alts' => [$front->id => str_repeat('a', 256)],
    ])->assertSessionHasErrors(["image_alts.{$front->id}" => 'The alt text field must not be greater than 255 characters.']);
});

// The form shows these beside the images, so they name an image rather than its key.
it('rejects images that do not exist', function (mixed $images, string $error, string $message) {
    signInAdmin();

    $this->post(route('larasell.admin.products.store'), ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible', 'images' => $images])
        ->assertSessionHasErrors([$error => $message]);

    expect(Product::query()->exists())->toBeFalse();
})->with([
    'not a list' => ['front', 'images', 'The images field must be an array.'],
    'missing image' => [['999'], 'images.0', 'The selected image is invalid.'],
    'not an id' => [['front'], 'images.0', 'The image field must be an integer.'],
]);

it('rejects uploads that are not one image', function (Closure $image) {
    Storage::fake('public');
    config(['larasell.images.disk' => 'public']);
    signInAdmin();

    $this->postJson(route('larasell.admin.products.images.store'), ['image' => $image()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    expect(ProductImage::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
})->with([
    'none' => fn () => null,
    // One at a time, so a failure can't leave some of a request's images stored.
    'several' => fn () => [UploadedFile::fake()->image('front.png'), UploadedFile::fake()->image('back.png')],
    'a document' => fn () => UploadedFile::fake()->create('manual.pdf', 10, 'application/pdf'),
    'an SVG' => fn () => UploadedFile::fake()->create('logo.svg', 1, 'image/svg+xml'),
    'too large' => fn () => UploadedFile::fake()->image('huge.png')->size(10 * 1024 + 1),
]);

// PHP drops files over its upload_max_filesize, which defaults to 2 MB, before the size rule runs.
it('explains images that PHP could not receive', function () {
    signInAdmin();
    $file = new UploadedFile(UploadedFile::fake()->image('huge.png')->getPathname(), 'huge.png', 'image/png', UPLOAD_ERR_INI_SIZE, true);

    $this->postJson(route('larasell.admin.products.images.store'), ['image' => $file])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image' => 'The image may be larger than the server accepts.']);
});

it('returns 404 for editing a product that does not exist', function () {
    signInAdmin();

    $this->get(route('larasell.admin.products.edit', 999))->assertNotFound();
});

it('updates a product and stays on its page, keeping its price', function () {
    signInAdmin();

    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Rain Boots', 'slug' => 'rain-boots', 'status' => 'hidden'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('larasell.admin.products.edit', $boots));

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

    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Rain Boots', 'slug' => 'rain-boots', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    $boots->refresh();

    expect($boots->name->all())->toBe(['en' => 'Rain Boots', 'de' => 'Stiefel'])
        ->and($boots->slug->all())->toBe(['en' => 'rain-boots', 'de' => 'stiefel']);
});

it('rejects an invalid product', function (array $input, string $error) {
    signInAdmin();

    Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $this->post(route('larasell.admin.products.store'), [...['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible'], ...$input])
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

    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Boots', 'slug' => 'boots', 'status' => 'visible'])
        ->assertSessionHasNoErrors();
    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Boots', 'slug' => 'socks', 'status' => 'visible'])
        ->assertSessionHasErrors('slug');
});

it('does not let guests create or edit products', function () {
    $boots = Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $this->get(route('larasell.admin.products.create'))->assertRedirect(route('larasell.admin.login'));
    $this->post(route('larasell.admin.products.store'), ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible'])->assertRedirect(route('larasell.admin.login'));
    $this->get(route('larasell.admin.products.edit', $boots))->assertRedirect(route('larasell.admin.login'));
    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Socks', 'slug' => 'socks', 'status' => 'visible'])->assertRedirect(route('larasell.admin.login'));
    $this->post(route('larasell.admin.products.images.store'), ['image' => UploadedFile::fake()->image('front.png')])->assertRedirect(route('larasell.admin.login'));

    expect(Product::query()->sole()->name->get())->toBe('Boots')
        ->and(ProductImage::query()->count())->toBe(0);
});

it('offers to delete each product', function () {
    signInAdmin();

    Product::create(['slug' => 'boots', 'name' => 'Boots', 'price' => Price::of(1000)]);

    $this->get(route('larasell.admin.products.index'))
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

    $delete = $this->get(route('larasell.admin.products.index'))->inertiaProps('table.rows.0._actions.0.url');

    $this->from(route('larasell.admin.products.index'))->post($delete)->assertRedirect(route('larasell.admin.products.index'));

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

    $delete = $this->get(route('larasell.admin.products.index'))->inertiaProps('table.rows.0._actions.0.url');

    $this->from(route('larasell.admin.products.index'))->post($delete)->assertRedirect(route('larasell.admin.products.index'));

    expect(DB::table('larasell_category_product')->where('product_id', $boots->id)->exists())->toBeFalse()
        ->and($shoes->fresh()?->products()->pluck('larasell_products.id')->all())->toBe([$socks->id]);
});

it('redirects guests away from the products', function () {
    $this->get(route('larasell.admin.products.index'))->assertRedirect(route('larasell.admin.login'));
});

it('shows Products in the navigation between Home and Categories', function () {
    signInAdmin();

    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => collect($items)->pluck('label')->all() === ['Home', 'Products', 'Categories', 'Customers', 'Settings']));
});

it('turns the products off when disabled in the config', function () {
    $this->bootingWith(fn () => config(['larasell.admin.products.enabled' => false]));
    signInAdmin();

    expect(Route::has('larasell.admin.products.index'))->toBeFalse();

    // The routes aren't registered, so there are no route names to use.
    $this->get('/admin/products')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->get('/admin/products/create')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->post('/admin/products')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->post('/admin/products/images')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => ! collect($items)->pluck('label')->contains('Products')));
});
