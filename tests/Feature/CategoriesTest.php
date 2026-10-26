<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;
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

function product(string $name, Category $category, Visibility $status = Visibility::Visible): Product
{
    $product = Product::create([
        'slug' => str($name)->slug()->value(),
        'name' => $name,
        'price' => Price::of(1000),
        'status' => $status,
    ]);
    $product->categories()->attach($category);

    return $product;
}

it('redirects the index to the top-most root category', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    // The lowest id belongs to a child, so the first category overall is not the target.
    $shirts = category('Shirts');
    $clothing = category('Clothing');
    $shirts->update(['parent_id' => $clothing->id]);
    category('Shoes');

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.index'))
        ->assertRedirect(route('larasell.admin.categories.show', $clothing));
});

it('shows the index when the store has no categories', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Category/Index', shouldExist: false)
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
        ->get(route('larasell.admin.categories.show', $shoes))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Category/Show', shouldExist: false)
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
        ->get(route('larasell.admin.categories.show', $shirts))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.0.url', route('larasell.admin.categories.show', $clothing))
            ->where('categories.0.children.0.url', route('larasell.admin.categories.show', $shirts)));
});

it('shows a category, including a hidden one, next to the tree', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $clothing = category('Clothing');
    $linen = category('Linen', $clothing, Visibility::Hidden);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $linen))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Category/Show', shouldExist: false)
            ->where('category.id', $linen->id)
            ->where('category.name', 'Linen')
            ->where('categories.0.children.0.name', 'Linen'));
});

it('shows the tree without a category, with a 404, for a category that does not exist', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    category('Clothing');

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', 999))
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Category/Show', shouldExist: false)
            ->where('category', null)
            ->missing('updateUrl')
            ->where('categories.0.name', 'Clothing'));
});

it('hides a category and goes back to the previous page', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $shirts = category('Shirts', $clothing);

    $this->actingAs($admin, 'larasell-admin')
        ->from(route('larasell.admin.categories.show', $clothing))
        ->post(route('larasell.admin.categories.hide', $shirts))
        ->assertRedirect(route('larasell.admin.categories.show', $clothing));

    expect($shirts->fresh()->status)->toBe(Visibility::Hidden)
        ->and($clothing->fresh()->status)->toBe(Visibility::Visible);
});

it('makes a hidden category visible again', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $sale = category('Sale', status: Visibility::Hidden);

    $this->actingAs($admin, 'larasell-admin')
        ->from(route('larasell.admin.categories.show', $sale))
        ->post(route('larasell.admin.categories.unhide', $sale))
        ->assertRedirect(route('larasell.admin.categories.show', $sale));

    expect($sale->fresh()->status)->toBe(Visibility::Visible);
});

it('gives every category in the tree its hide and unhide urls', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $clothing))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.0.hideUrl', route('larasell.admin.categories.hide', $clothing))
            ->where('categories.0.unhideUrl', route('larasell.admin.categories.unhide', $clothing)));
});

it('does not let guests change a category\'s visibility', function (string $action) {
    $clothing = category('Clothing');

    $this->post(route("larasell.admin.categories.{$action}", $clothing))
        ->assertRedirect(route('larasell.admin.login'));

    expect($clothing->fresh()->status)->toBe(Visibility::Visible);
})->with(['hide', 'unhide']);

it('links the category layout to the create page', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $clothing))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('createUrl', route('larasell.admin.categories.create')));
});

it('shows the create page next to the tree', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    category('Clothing');

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Category/Create', shouldExist: false)
            ->where('categories.0.name', 'Clothing')
            ->where('createUrl', route('larasell.admin.categories.create'))
            ->where('storeUrl', route('larasell.admin.categories.store'))
            ->where('indexUrl', route('larasell.admin.categories.index')));
});

it('creates a root category and opens it', function (Visibility $status) {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $response = $this->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.categories.store'), ['name' => 'Summer Sale', 'slug' => 'summer-sale', 'status' => $status->value]);

    $category = Category::query()->sole();

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('larasell.admin.categories.show', $category));

    expect($category->name->all())->toBe(['en' => 'Summer Sale'])
        ->and($category->slug->all())->toBe(['en' => 'summer-sale'])
        ->and($category->status)->toBe($status)
        ->and($category->parent_id)->toBeNull();
})->with([
    'visible' => [Visibility::Visible],
    'hidden' => [Visibility::Hidden],
]);

it('shows the category\'s values to edit on its page', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $linen = category('Linen', $clothing, Visibility::Hidden);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $linen))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Category/Show', shouldExist: false)
            ->where('categories.0.children.0.name', 'Linen')
            ->where('category.id', $linen->id)
            ->where('category.name', 'Linen')
            ->where('category.slug', 'linen')
            ->where('category.visible', false)
            ->where('createUrl', route('larasell.admin.categories.create'))
            ->where('updateUrl', route('larasell.admin.categories.update', $linen)));
});

it('updates a category and stays on its page', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $shirts = category('Shirts', $clothing);

    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.categories.update', $shirts), ['name' => 'Tops', 'slug' => 'tops', 'status' => 'hidden'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('larasell.admin.categories.show', $shirts));

    $shirts->refresh();

    expect($shirts->name->get())->toBe('Tops')
        ->and($shirts->slug->get())->toBe('tops')
        ->and($shirts->status)->toBe(Visibility::Hidden)
        ->and($shirts->parent_id)->toBe($clothing->id);
});

it('only changes the current locale\'s name and slug', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = Category::create([
        'name' => ['en' => 'Shoes', 'de' => 'Schuhe'],
        'slug' => ['en' => 'shoes', 'de' => 'schuhe'],
        'status' => Visibility::Visible,
    ]);

    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.categories.update', $shoes), ['name' => 'Footwear', 'slug' => 'footwear', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    $shoes->refresh();

    expect($shoes->name->all())->toBe(['en' => 'Footwear', 'de' => 'Schuhe'])
        ->and($shoes->slug->all())->toBe(['en' => 'footwear', 'de' => 'schuhe']);
});

it('rejects an invalid category', function (array $input, string $error) {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    category('Shoes');

    $this->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.categories.store'), [...['name' => 'Sale', 'slug' => 'sale', 'status' => 'visible'], ...$input])
        ->assertSessionHasErrors($error);

    expect(Category::query()->count())->toBe(1);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'too long name' => [['name' => str_repeat('a', 256)], 'name'],
    'missing slug' => [['slug' => ''], 'slug'],
    'slug with spaces' => [['slug' => 'summer sale'], 'slug'],
    'slug with capitals' => [['slug' => 'Sale'], 'slug'],
    'slug with a trailing dash' => [['slug' => 'sale-'], 'slug'],
    'slug of another category' => [['slug' => 'shoes'], 'slug'],
    'missing visibility' => [['status' => null], 'status'],
    'unknown visibility' => [['status' => 'secret'], 'status'],
]);

it('lets a category keep its own slug', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');

    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.categories.update', $shoes), ['name' => 'All Shoes', 'slug' => 'shoes', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    expect($shoes->fresh()->name->get())->toBe('All Shoes');
});

it('does not let a category take the slug of another one', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    category('Shoes');
    $boots = category('Boots');

    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.categories.update', $boots), ['name' => 'Boots', 'slug' => 'shoes', 'status' => 'visible'])
        ->assertSessionHasErrors('slug');

    expect($boots->fresh()->slug->get())->toBe('boots');
});

it('does not let guests create or edit categories', function () {
    $shoes = category('Shoes');

    $this->get(route('larasell.admin.categories.create'))->assertRedirect(route('larasell.admin.login'));
    $this->post(route('larasell.admin.categories.store'), ['name' => 'Sale', 'slug' => 'sale', 'status' => 'visible'])->assertRedirect(route('larasell.admin.login'));
    $this->put(route('larasell.admin.categories.update', $shoes), ['name' => 'Boots', 'slug' => 'boots', 'status' => 'hidden'])->assertRedirect(route('larasell.admin.login'));

    expect(Category::query()->count())->toBe(1)
        ->and($shoes->fresh()->name->get())->toBe('Shoes');
});

it('lists the products of the category on its page', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');
    $boots = product('Boots', $shoes);
    $sandals = product('Sandals', $shoes, Visibility::Hidden);
    product('Scarf', category('Accessories'));

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('products.title', 'Products')
            ->where('products.columns', [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Visibility', 'type' => 'text'],
            ])
            ->has('products.rows', 2)
            ->where('products.rows.0.id', $boots->id)
            ->where('products.rows.0.name', 'Boots')
            ->where('products.rows.0.status', 'Visible')
            ->where('products.rows.1.id', $sandals->id)
            ->where('products.rows.1.name', 'Sandals')
            ->where('products.rows.1.status', 'Hidden')
            ->where('products.rows.0._actions.1.label', 'Remove')
            ->where('products.rows.0._actions.1.method', 'post')
            ->where('products.rows.0._actions.1.destructive', true)
            ->where('products.rows.0._actions.1.icon', 'FolderReduce'));
});

it('does not list products on the create page', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page->missing('products'));
});

it('removes a product from the category but keeps it in the store and its other categories', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');
    $sale = category('Sale');
    $boots = product('Boots', $shoes);
    $boots->categories()->attach($sale);
    product('Sandals', $shoes);

    $url = $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $shoes))
        ->viewData('page')['props']['products']['rows'][0]['_actions'][1]['url'];

    $this->from(route('larasell.admin.categories.show', $shoes))
        ->post($url)
        ->assertRedirect(route('larasell.admin.categories.show', $shoes));

    expect($shoes->products()->pluck('name')->map->get()->all())->toBe(['Sandals'])
        ->and($sale->products()->pluck('larasell_products.id')->all())->toBe([$boots->id])
        ->and(Product::query()->count())->toBe(2);
});

it('only removes products that are in the category', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');
    $boots = product('Boots', $shoes);
    $scarf = product('Scarf', category('Accessories'));

    $url = $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $shoes))
        ->viewData('page')['props']['products']['rows'][0]['_actions'][1]['url'];

    $this->post(str_replace("/{$boots->id}/", "/{$scarf->id}/", $url))->assertNotFound();

    expect($scarf->categories()->count())->toBe(1);
});

it('gives the category page the urls to find and add products', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('availableProductsUrl', route('larasell.admin.categories.available-products.index', $shoes))
            ->where('addProductUrl', route('larasell.admin.categories.products.store', $shoes)));
});

it('finds the products by name that are not in the category yet', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');
    $accessories = category('Accessories');
    product('Leather boots', $shoes);
    $rainBoots = product('Rain boots', $accessories);
    $hiddenBoots = product('Hidden boots', $accessories, Visibility::Hidden);
    product('Scarf', $accessories);

    $this->actingAs($admin, 'larasell-admin')
        ->getJson(route('larasell.admin.categories.available-products.index', [$shoes, 'search' => 'BOOTS']))
        ->assertOk()
        ->assertExactJson(['products' => [
            ['id' => $rainBoots->id, 'name' => 'Rain boots'],
            ['id' => $hiddenBoots->id, 'name' => 'Hidden boots'],
        ]]);
});

it('suggests products without a search', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');
    $scarf = product('Scarf', category('Accessories'));

    $this->actingAs($admin, 'larasell-admin')
        ->getJson(route('larasell.admin.categories.available-products.index', $shoes))
        ->assertExactJson(['products' => [['id' => $scarf->id, 'name' => 'Scarf']]]);
});

it('adds a product to the category and goes back', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');
    $sale = category('Sale');
    $boots = product('Boots', $sale);

    $this->actingAs($admin, 'larasell-admin')
        ->from(route('larasell.admin.categories.show', $shoes))
        ->post(route('larasell.admin.categories.products.store', $shoes), ['product_id' => $boots->id])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('larasell.admin.categories.show', $shoes));

    expect($shoes->products()->pluck('larasell_products.id')->all())->toBe([$boots->id])
        ->and($sale->products()->pluck('larasell_products.id')->all())->toBe([$boots->id]);
});

it('rejects adding a product that is missing or already in the category', function (Closure $productId) {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');
    $boots = product('Boots', $shoes);

    $this->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.categories.products.store', $shoes), ['product_id' => $productId($boots)])
        ->assertSessionHasErrors('product_id');

    expect($shoes->products()->count())->toBe(1);
})->with([
    'none' => [fn (Product $boots) => null],
    'unknown' => [fn (Product $boots) => 999],
    'already in the category' => [fn (Product $boots) => $boots->id],
]);

it('does not let guests find or add products', function () {
    $shoes = category('Shoes');
    $boots = product('Boots', category('Sale'));

    $this->get(route('larasell.admin.categories.available-products.index', $shoes))->assertRedirect(route('larasell.admin.login'));
    $this->post(route('larasell.admin.categories.products.store', $shoes), ['product_id' => $boots->id])->assertRedirect(route('larasell.admin.login'));

    expect($shoes->products()->count())->toBe(0);
});

it('gives every category in the tree its delete url', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $clothing))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.0.deleteUrl', route('larasell.admin.categories.destroy', $clothing)));
});

it('deletes a category with all its descendants but keeps its parent, siblings and products', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $men = category('Men', $clothing);
    $jackets = category('Jackets', $men);
    category('Rain jackets', $jackets);
    category('Shirts', $men);
    category('Women', $clothing);
    category('Shoes');
    $coat = product('Coat', $jackets);

    $this->actingAs($admin, 'larasell-admin')
        ->from(route('larasell.admin.categories.show', $clothing))
        ->delete(route('larasell.admin.categories.destroy', $men), ['confirmation' => 'Men'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('larasell.admin.categories.show', $clothing));

    expect(Category::query()->orderBy('id')->pluck('id')->all())
        ->toBe(Category::query()->whereIn('slug->en', ['clothing', 'women', 'shoes'])->orderBy('id')->pluck('id')->all())
        ->and(Category::query()->count())->toBe(3)
        ->and($coat->fresh())->not->toBeNull()
        ->and($coat->categories()->count())->toBe(0);
});

it('fires the model events for every deleted category, children before their parent', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $men = category('Men', $clothing);
    $jackets = category('Jackets', $men);
    $rain = category('Rain jackets', $jackets);
    $shirts = category('Shirts', $men);
    category('Women', $clothing);

    $deleting = [];
    $deleted = [];
    Category::deleting(function (Category $category) use (&$deleting) {
        // The parent still exists while its children go, so listeners see the tree intact.
        $deleting[] = [$category->id, $category->fresh()?->parent_id];
    });
    Category::deleted(function (Category $category) use (&$deleted) {
        $deleted[] = $category->id;
    });

    $this->actingAs($admin, 'larasell-admin')
        ->delete(route('larasell.admin.categories.destroy', $men), ['confirmation' => 'Men'])
        ->assertSessionHasNoErrors();

    $position = array_flip($deleted);

    expect($deleted)->toEqualCanonicalizing([$men->id, $jackets->id, $rain->id, $shirts->id])
        ->and($deleting)->toEqualCanonicalizing([
            [$men->id, $clothing->id],
            [$jackets->id, $men->id],
            [$rain->id, $jackets->id],
            [$shirts->id, $men->id],
        ])
        ->and($position[$rain->id])->toBeLessThan($position[$jackets->id])
        ->and($position[$jackets->id])->toBeLessThan($position[$men->id])
        ->and($position[$shirts->id])->toBeLessThan($position[$men->id]);
});

it('requires the category name to delete it', function (?string $typed) {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    category('Men', $clothing);

    $this->actingAs($admin, 'larasell-admin')
        ->delete(route('larasell.admin.categories.destroy', $clothing), array_filter(['confirmation' => $typed]))
        ->assertSessionHasErrors('confirmation');

    expect(Category::query()->count())->toBe(2);
})->with([
    'missing' => [null],
    'wrong' => ['Men'],
    'wrong case' => ['clothing'],
]);

it('goes to the categories index after deleting the category that was open', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $men = category('Men', $clothing);

    $this->actingAs($admin, 'larasell-admin')
        ->from(route('larasell.admin.categories.show', $men))
        ->delete(route('larasell.admin.categories.destroy', $clothing), ['confirmation' => 'Clothing'])
        ->assertRedirect(route('larasell.admin.categories.index'));
});

it('does not let guests delete a category', function () {
    $clothing = category('Clothing');

    $this->delete(route('larasell.admin.categories.destroy', $clothing), ['confirmation' => 'Clothing'])
        ->assertRedirect(route('larasell.admin.login'));

    expect(Category::query()->count())->toBe(1);
});

it('gives every category in the tree a url to create a category inside it', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $men = category('Men', $clothing);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $clothing))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.0.createChildUrl', route('larasell.admin.categories.create', ['parent' => $clothing]))
            ->where('categories.0.children.0.createChildUrl', route('larasell.admin.categories.create', ['parent' => $men])));
});

it('shows the create page for a category inside another one', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.create', ['parent' => $clothing]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Category/Create', shouldExist: false)
            ->where('parent.id', $clothing->id)
            ->where('parent.name', 'Clothing'));
});

it('shows the create page without a parent for a root category', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('parent', null));
});

it('returns 404 for the create page inside a category that does not exist', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.create', ['parent' => 999]))
        ->assertNotFound();
});

it('creates a category inside another one and opens it', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $men = category('Men', $clothing);

    $response = $this->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.categories.store'), ['name' => 'Jackets', 'slug' => 'jackets', 'status' => 'visible', 'parent_id' => $men->id]);

    $jackets = Category::query()->where('slug->en', 'jackets')->sole();

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('larasell.admin.categories.show', $jackets));

    expect($jackets->parent_id)->toBe($men->id);
});

it('rejects a parent that does not exist', function (mixed $parentId) {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.categories.store'), ['name' => 'Jackets', 'slug' => 'jackets', 'status' => 'visible', 'parent_id' => $parentId])
        ->assertSessionHasErrors('parent_id');

    expect(Category::query()->count())->toBe(0);
})->with([
    'unknown' => [999],
    'not a number' => ['clothing'],
]);

it('does not move a category to another parent when it is updated', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $shoes = category('Shoes');
    $men = category('Men', $clothing);

    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.categories.update', $men), ['name' => 'Men', 'slug' => 'men', 'status' => 'visible', 'parent_id' => $shoes->id])
        ->assertSessionHasNoErrors();

    expect($men->fresh()->parent_id)->toBe($clothing->id);
});

it('gives every category in the tree its move url', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $clothing))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('categories.0.moveUrl', route('larasell.admin.categories.move', $clothing)));
});

it('moves a category with its children into another category', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $men = category('Men', $clothing);
    $shirts = category('Shirts', $men);
    $sale = category('Sale');

    $this->actingAs($admin, 'larasell-admin')
        ->from(route('larasell.admin.categories.show', $clothing))
        ->post(route('larasell.admin.categories.move', $men), ['parent_id' => $sale->id])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('larasell.admin.categories.show', $clothing));

    expect($men->fresh()->parent_id)->toBe($sale->id)
        ->and($shirts->fresh()->parent_id)->toBe($men->id);
});

it('moves a category to the root', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $men = category('Men', category('Clothing'));

    $this->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.categories.move', $men), ['parent_id' => null])
        ->assertSessionHasNoErrors();

    expect($men->fresh()->parent_id)->toBeNull();
});

it('does not move a category into itself or a category below it', function (string $into) {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $clothing = category('Clothing');
    $men = category('Men', $clothing);
    $jackets = category('Jackets', $men);
    $rain = category('Rain jackets', $jackets);
    $target = ['itself' => $men, 'its child' => $jackets, 'its grandchild' => $rain][$into];

    $this->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.categories.move', $men), ['parent_id' => $target->id])
        ->assertSessionHasErrors('parent_id');

    expect($men->fresh()->parent_id)->toBe($clothing->id);
})->with(['itself', 'its child', 'its grandchild']);

it('does not move a category into one that does not exist', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $men = category('Men', category('Clothing'));

    $this->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.categories.move', $men), ['parent_id' => 999])
        ->assertSessionHasErrors('parent_id');
});

it('does not let guests move a category', function () {
    $clothing = category('Clothing');
    $sale = category('Sale');

    $this->post(route('larasell.admin.categories.move', $clothing), ['parent_id' => $sale->id])
        ->assertRedirect(route('larasell.admin.login'));

    expect($clothing->fresh()->parent_id)->toBeNull();
});

it('links to creating a product from a category\'s page and the page for adding one', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');
    $link = fn (AssertableInertia $page) => $page->where('createProductUrl', route('larasell.admin.products.create'));

    $this->actingAs($admin, 'larasell-admin');
    $this->get(route('larasell.admin.categories.show', $shoes))->assertInertia($link);
    $this->get(route('larasell.admin.categories.create'))->assertInertia($link);
});

it('does not link to creating a product when the products are turned off', function () {
    $this->bootingWith(fn () => config(['larasell.admin.products.enabled' => false]));
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');
    $noLink = fn (AssertableInertia $page) => $page->where('createProductUrl', null);

    $this->actingAs($admin, 'larasell-admin');
    $this->get(route('larasell.admin.categories.show', $shoes))->assertInertia($noLink);
    $this->get(route('larasell.admin.categories.create'))->assertInertia($noLink);
});

it('stops linking to creating a product from a category\'s page once the store has products', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $shoes = category('Shoes');
    // In another category: the store has a product to add, so the page offers that instead.
    product('Socks', category('Clothing'));

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('createProductUrl', null));
});
