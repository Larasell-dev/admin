<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Search\Searches;
use Larasell\Admin\Platform\Search\SearchQuery;
use Larasell\Admin\Product\Search\ProductSearch;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Models\Customer;
use Larasell\Larasell\Models\Order;
use Larasell\Larasell\Price;
use Larasell\Larasell\SalesChannels\Actions\CreateSalesChannel;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\SalesChannels\SalesContext;

beforeEach(function () {
    config(['larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN], 'larasell.locale.default' => Locale::ENGLISH]);
});

function signInSearchAdmin(): void
{
    test()->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
}

/**
 * @param  array<string, string>  $name
 * @param  array<string, mixed>  $attributes
 */
function searchableProduct(array $name, array $attributes = []): Product
{
    return Product::create([
        'name' => $name,
        'price' => Price::of(1000),
        ...$attributes,
    ]);
}

/**
 * @param  array<string, string>  $name
 */
function searchableCategory(array $name): Category
{
    return Category::create([
        'name' => $name,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function searchableOrder(string $number, array $attributes = []): Order
{
    return Order::createIn(SalesContext::for('default', 'en'), [
        'number' => $number,
        'customer_email' => 'grace@example.com',
        'customer_name' => 'Grace Hopper',
        'currency' => Currency::EUR,
        'subtotal' => Price::of(0),
        'total' => Price::of(0),
        ...$attributes,
    ]);
}

function switchSearchContentLocale(string $locale): void
{
    test()->put(route('larasell.admin.content-locale.update'), ['locale' => $locale])->assertSessionHasNoErrors();
}

it('finds products by their name in any locale and shows the name in the content locale', function () {
    // Arrange
    signInSearchAdmin();
    $boots = searchableProduct(['en' => 'Chelsea Boots', 'de' => 'Chelsea-Stiefel']);
    $raincoat = searchableProduct(['en' => 'Raincoat', 'de' => 'Regenmantel mit Stiefeltasche']);
    searchableProduct(['en' => 'Scarf', 'de' => 'Schal']);

    // Act & Assert
    $this->getJson(route('larasell.admin.search.products', ['q' => 'stiefel']))
        ->assertOk()
        ->assertExactJson(['results' => [
            [
                'id' => $boots->id,
                'label' => 'Chelsea Boots',
                'url' => route('larasell.admin.products.edit', $boots),
                'match' => ['label' => 'German', 'value' => 'Chelsea-Stiefel'],
            ],
            [
                'id' => $raincoat->id,
                'label' => 'Raincoat',
                'url' => route('larasell.admin.products.edit', $raincoat),
                'match' => ['label' => 'German', 'value' => 'Regenmantel mit Stiefeltasche'],
            ],
        ]]);

    switchSearchContentLocale('de');

    $this->getJson(route('larasell.admin.search.products', ['q' => 'boots']))
        ->assertExactJson(['results' => [[
            'id' => $boots->id,
            'label' => 'Chelsea-Stiefel',
            'url' => route('larasell.admin.products.edit', $boots),
            'match' => ['label' => 'English', 'value' => 'Chelsea Boots'],
        ]]]);
});

it('ranks products whose name matches in the content locale first', function () {
    // Arrange
    signInSearchAdmin();
    $coat = searchableProduct(['en' => 'Raincoat', 'de' => 'Regenmantel']);
    $clock = searchableProduct(['en' => 'Mantel clock', 'de' => 'Kaminuhr']);

    // Act & Assert
    $this->getJson(route('larasell.admin.search.products', ['q' => 'mantel']))
        ->assertJsonPath('results.*.id', [$clock->id, $coat->id]);

    switchSearchContentLocale('de');

    $this->getJson(route('larasell.admin.search.products', ['q' => 'mantel']))
        ->assertJsonPath('results.*.id', [$coat->id, $clock->id]);
});

it('says nothing more when the name in the content locale matched', function () {
    // Arrange
    signInSearchAdmin();
    $shirt = searchableProduct(['en' => 'Oxford Shirt', 'de' => 'Oxford-Hemd']);

    // Act & Assert
    // Case and spaces don't matter, like in the menu's own search.
    $this->getJson(route('larasell.admin.search.products', ['q' => 'OXFORDSHIRT']))
        ->assertExactJson(['results' => [[
            'id' => $shirt->id,
            'label' => 'Oxford Shirt',
            'url' => route('larasell.admin.products.edit', $shirt),
            'match' => null,
        ]]]);
});

it('finds products by their SKU, barcode and the SKUs of their variants', function () {
    // Arrange
    signInSearchAdmin();
    $boots = searchableProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['sku' => 'BT-100', 'barcode' => '4006381333931']);
    $scarf = searchableProduct(['en' => 'Scarf', 'de' => 'Schal'], ['sku' => 'SC-200']);
    $scarf->variants()->create(['sku' => 'SC-200-BT', 'combination_key' => 'red', 'position' => 1, 'is_default' => false]);

    // Act & Assert
    $this->getJson(route('larasell.admin.search.products', ['q' => 'bt-100']))
        ->assertJsonPath('results', [[
            'id' => $boots->id,
            'label' => 'Boots',
            'url' => route('larasell.admin.products.edit', $boots),
            'match' => ['label' => 'SKU', 'value' => 'BT-100'],
        ]]);

    $this->getJson(route('larasell.admin.search.products', ['q' => '638133']))
        ->assertJsonPath('results.0.match', ['label' => 'Barcode', 'value' => '4006381333931']);

    $this->getJson(route('larasell.admin.search.products', ['q' => '200-bt']))
        ->assertJsonPath('results', [[
            'id' => $scarf->id,
            'label' => 'Scarf',
            'url' => route('larasell.admin.products.edit', $scarf),
            'match' => ['label' => 'Variant SKU', 'value' => 'SC-200-BT'],
        ]]);
});

it('takes the search literally and returns at most ten products', function () {
    // Arrange
    signInSearchAdmin();
    searchableProduct(['en' => 'Socks', 'de' => 'Socken']);
    $sale = searchableProduct(['en' => '50% off socks', 'de' => '50% Socken']);

    // Act & Assert
    $this->getJson(route('larasell.admin.search.products', ['q' => '%']))
        ->assertJsonPath('results.*.id', [$sale->id]);

    foreach (range(1, 12) as $number) {
        searchableProduct(['en' => "Belt {$number}", 'de' => "Gürtel {$number}"]);
    }

    $this->getJson(route('larasell.admin.search.products', ['q' => 'belt']))
        ->assertJsonCount(10, 'results');
});

it('finds orders by their number and their customer\'s name and email', function () {
    // Arrange
    signInSearchAdmin();
    $grace = searchableOrder('1042');
    $alan = searchableOrder('2001', ['customer_name' => 'Alan Turing', 'customer_email' => 'alan@bletchley.example']);

    // Act & Assert
    $this->getJson(route('larasell.admin.search.orders', ['q' => '104']))
        ->assertExactJson(['results' => [[
            'id' => $grace->id,
            'label' => '1042',
            'url' => route('larasell.admin.orders.show', $grace),
            'match' => null,
        ]]]);

    $this->getJson(route('larasell.admin.search.orders', ['q' => 'alanturing']))
        ->assertJsonPath('results', [[
            'id' => $alan->id,
            'label' => '2001',
            'url' => route('larasell.admin.orders.show', $alan),
            'match' => ['label' => 'Customer', 'value' => 'Alan Turing'],
        ]]);

    $this->getJson(route('larasell.admin.search.orders', ['q' => 'bletchley']))
        ->assertJsonPath('results.*.match', [['label' => 'Email', 'value' => 'alan@bletchley.example']]);
});

it('finds customers by their full name', function () {
    // Arrange
    signInSearchAdmin();
    $ada = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    Customer::create(['first_name' => 'Alan', 'last_name' => 'Turing']);

    // Act & Assert
    $this->getJson(route('larasell.admin.search.customers', ['q' => 'a lovel']))
        ->assertExactJson(['results' => [[
            'id' => $ada->id,
            'label' => 'Ada Lovelace',
            'url' => route('larasell.admin.customers.edit', $ada),
            'match' => null,
        ]]]);
});

it('finds categories by their name in any locale and shows the name in the content locale', function () {
    // Arrange
    signInSearchAdmin();
    $shoes = searchableCategory(['en' => 'Shoes', 'de' => 'Schuhe']);
    searchableCategory(['en' => 'Hats', 'de' => 'Hüte']);

    // Act & Assert
    $this->getJson(route('larasell.admin.search.categories', ['q' => 'schu']))
        ->assertExactJson(['results' => [[
            'id' => $shoes->id,
            'label' => 'Shoes',
            'url' => route('larasell.admin.categories.show', $shoes),
            'match' => ['label' => 'German', 'value' => 'Schuhe'],
        ]]]);
});

it('finds sales channels by their name', function () {
    // Arrange
    signInSearchAdmin();
    SalesChannel::query()->whereKey(1)->update(['name' => 'Webshop']);
    app(CreateSalesChannel::class)->handle(['key' => 'b2b', 'name' => 'Wholesale Shop', 'type' => 'storefront', 'locales' => ['en'], 'currencies' => ['USD']]);
    $wholesale = SalesChannel::query()->where('key', 'b2b')->firstOrFail();

    // Act & Assert
    $this->getJson(route('larasell.admin.search.sales-channels', ['q' => 'wholesaleshop']))
        ->assertExactJson(['results' => [[
            'id' => $wholesale->id,
            'label' => 'Wholesale Shop',
            'url' => route('larasell.admin.sales-channels.edit', $wholesale),
            'match' => null,
        ]]]);
});

it('needs a search of at most 200 characters', function (string $route) {
    // Arrange
    signInSearchAdmin();

    // Act & Assert
    $this->getJson(route($route))->assertUnprocessable()->assertJsonValidationErrors('q');
    $this->getJson(route($route, ['q' => str_repeat('a', 201)]))->assertJsonValidationErrors('q');
})->with([
    'products' => 'larasell.admin.search.products',
    'orders' => 'larasell.admin.search.orders',
    'customers' => 'larasell.admin.search.customers',
    'categories' => 'larasell.admin.search.categories',
    'sales channels' => 'larasell.admin.search.sales-channels',
]);

it('searches only for signed-in admins', function (string $route) {
    // Arrange
    searchableProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    // Act & Assert
    $this->getJson(route($route, ['q' => 'boots']))->assertRedirect(route('larasell.admin.login'));
})->with([
    'larasell.admin.search.products',
    'larasell.admin.search.orders',
    'larasell.admin.search.customers',
    'larasell.admin.search.categories',
    'larasell.admin.search.sales-channels',
]);

it('searches with the product search bound in the container', function () {
    // Arrange
    signInSearchAdmin();
    $boots = searchableProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    app()->bind(ProductSearch::class, fn () => new class($boots) implements ProductSearch
    {
        public function __construct(private readonly Product $boots) {}

        public function search(SearchQuery $query): Collection
        {
            return $query->text === 'anything' && $query->locale === Locale::ENGLISH ? collect([$this->boots]) : collect();
        }
    });

    // Act & Assert
    $this->getJson(route('larasell.admin.search.products', ['q' => 'anything']))
        ->assertJsonPath('results.*.id', [$boots->id]);
});

it('shares the searches of the enabled modules with signed-in admins', function () {
    // Act & Assert
    $this->get(route('larasell.admin.login'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('commandMenu', null));

    signInSearchAdmin();

    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('commandMenu.searches', [
            ['id' => 'products', 'label' => 'Search products', 'icon' => 'ShoppingBag', 'placeholder' => 'Search by name, SKU or barcode', 'url' => route('larasell.admin.search.products')],
            ['id' => 'categories', 'label' => 'Search categories', 'icon' => 'Folders', 'placeholder' => 'Search by name', 'url' => route('larasell.admin.search.categories')],
            ['id' => 'customers', 'label' => 'Search customers', 'icon' => 'Group', 'placeholder' => 'Search by name', 'url' => route('larasell.admin.search.customers')],
            ['id' => 'orders', 'label' => 'Search orders', 'icon' => 'Receipt', 'placeholder' => 'Search by number, customer or email', 'url' => route('larasell.admin.search.orders')],
            ['id' => 'sales-channels', 'label' => 'Search sales channels', 'icon' => 'Store', 'placeholder' => 'Search by name', 'url' => route('larasell.admin.search.sales-channels')],
        ]));

    $this->bootingWith(fn () => config(['larasell.admin.orders.enabled' => false]));
    signInSearchAdmin();

    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('commandMenu.searches', fn ($searches) => collect($searches)->pluck('id')->all() === ['products', 'categories', 'customers', 'sales-channels']));
});

it('lets a plugin add its own search to the command menu', function () {
    // Arrange
    $this->bootingWith(fn () => app()->booted(fn () => app(Searches::class)->add(
        'gift-cards',
        label: 'Search gift cards',
        icon: Icon::Gift,
        placeholder: 'Search by code',
        route: 'larasell.admin.search.products',
    )));
    signInSearchAdmin();

    // Act & Assert
    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('commandMenu.searches.5', [
            'id' => 'gift-cards',
            'label' => 'Search gift cards',
            'icon' => 'Gift',
            'placeholder' => 'Search by code',
            'url' => route('larasell.admin.search.products'),
        ]));
});
