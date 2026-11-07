<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Plan;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Facades\Entitlement;
use Larasell\Larasell\Price;

beforeEach(function () {
    config(['larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN], 'larasell.locale.default' => Locale::ENGLISH]);

    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

/**
 * @param  array<string, string>  $name
 */
function translatedCategory(array $name, ?Category $parent = null): Category
{
    return Category::create(['parent_id' => $parent?->id, 'name' => $name, 'status' => Visibility::Visible]);
}

function pickCategoryContentLocale(string $locale): void
{
    test()->from(route('larasell.admin.categories.index'))
        ->put(route('larasell.admin.content-locale.update'), ['locale' => $locale])
        ->assertSessionHasNoErrors();
}

it('offers the store\'s locales on the category page and when adding a category', function () {
    // Arrange
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe']);

    // Act & Assert
    $this->get(route('larasell.admin.categories.show', $shoes))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('category.name', 'Shoes')
            ->where('contentLocale', [
                'current' => 'en',
                'options' => [
                    ['value' => 'en', 'label' => 'English'],
                    ['value' => 'de', 'label' => 'German'],
                ],
                'switchUrl' => route('larasell.admin.content-locale.update'),
            ]));

    $this->get(route('larasell.admin.categories.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('contentLocale.current', 'en'));
});

it('shows the category in the picked locale', function () {
    // Arrange
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe']);

    pickCategoryContentLocale('de');

    // Act & Assert
    $this->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('category.name', 'Schuhe')
            ->where('contentLocale.current', 'de'));
});

it('saves the category\'s name in the picked locale and keeps the other translations', function () {
    // Arrange
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe']);

    pickCategoryContentLocale('de');

    // Act
    $this->put(route('larasell.admin.categories.update', $shoes), ['name' => 'Schuhwerk', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    // Assert
    $shoes->refresh();

    expect($shoes->name->all())->toBe(['en' => 'Shoes', 'de' => 'Schuhwerk']);
});

it('creates a category in the picked locale', function () {
    // Arrange
    pickCategoryContentLocale('de');

    // Act
    $this->post(route('larasell.admin.categories.store'), ['name' => 'Taschen', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    // Assert
    $category = Category::query()->sole();

    expect($category->name->all())->toBe(['de' => 'Taschen']);
});

it('leaves an untranslated category name empty, with what the store shows instead', function () {
    // Arrange
    $shoes = translatedCategory(['en' => 'Shoes']);

    pickCategoryContentLocale('de');

    // Act & Assert
    $this->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('category.name', null)
            ->where('category.fallbackName', 'Shoes'));
});

it('only edits categories in the default locale without content translations', function () {
    // Arrange
    Entitlement::fake(Plan::Free);
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe']);

    // Act & Assert
    $this->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('category.name', 'Shoes')
            ->where('contentLocale.options', [['value' => 'en', 'label' => 'English']]));
});

it('lists the category\'s products by their name in the picked locale', function () {
    // Arrange
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe']);
    $boots = Product::create(['name' => ['en' => 'Boots', 'de' => 'Stiefel'], 'price' => Price::of(1000)]);
    $socks = Product::create(['name' => ['en' => 'Socks'], 'price' => Price::of(500)]);
    $shoes->products()->attach([$boots->id, $socks->id]);

    pickCategoryContentLocale('de');

    // Act & Assert
    $this->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('products.rows.0.name', 'Stiefel')
            // Untranslated, it shows what the store shows.
            ->where('products.rows.1.name', 'Socks'));
});

it('finds products to add by the name the picked locale shows', function () {
    // Arrange
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe']);
    Product::create(['name' => ['en' => 'Boots', 'de' => 'Stiefel'], 'price' => Price::of(1000)]);
    Product::create(['name' => ['en' => 'Socks'], 'price' => Price::of(500)]);

    pickCategoryContentLocale('de');

    $search = fn (string $term) => $this->getJson(route('larasell.admin.categories.available-products.index', [$shoes, 'search' => $term]))
        ->json('products.*.name');

    // Act & Assert
    expect($search(''))->toBe(['Stiefel', 'Socks'])
        ->and($search('stief'))->toBe(['Stiefel'])
        // Shown as Stiefel, so its English name doesn't find it.
        ->and($search('boots'))->toBe([])
        // Untranslated, it's found by the name it shows.
        ->and($search('socks'))->toBe(['Socks']);
});

it('names the categories in the sidebar tree in the picked locale', function () {
    // Arrange
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe']);
    translatedCategory(['en' => 'Boots', 'de' => 'Stiefel'], $shoes);
    translatedCategory(['en' => 'Bags']);

    pickCategoryContentLocale('de');

    $tree = fn (AssertableInertia $page) => $page
        ->where('categories.0.name', 'Schuhe')
        ->where('categories.0.children.0.name', 'Stiefel')
        // Untranslated, it shows what the store shows.
        ->where('categories.1.name', 'Bags');

    // Act & Assert
    $this->get(route('larasell.admin.categories.show', $shoes))->assertInertia($tree);
    $this->get(route('larasell.admin.categories.create'))->assertInertia($tree);
    $this->get(route('larasell.admin.categories.show', 999))->assertNotFound()->assertInertia($tree);
});
