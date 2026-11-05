<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Plan;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Facades\Entitlement;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;

beforeEach(function () {
    config(['larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN], 'larasell.locale.default' => Locale::ENGLISH]);

    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

/**
 * @param  array<string, string>  $name
 * @param  array<string, string>  $slug
 */
function translatedCategory(array $name, array $slug, ?Category $parent = null): Category
{
    return Category::create(['parent_id' => $parent?->id, 'name' => $name, 'slug' => $slug, 'status' => Visibility::Visible]);
}

function pickCategoryContentLocale(string $locale): void
{
    test()->from(route('larasell.admin.categories.index'))
        ->put(route('larasell.admin.content-locale.update'), ['locale' => $locale])
        ->assertSessionHasNoErrors();
}

it('offers the store\'s locales on the category page and when adding a category', function () {
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe'], ['en' => 'shoes', 'de' => 'schuhe']);

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
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe'], ['en' => 'shoes', 'de' => 'schuhe']);

    pickCategoryContentLocale('de');

    $this->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('category.name', 'Schuhe')
            ->where('category.slug', 'schuhe')
            ->where('contentLocale.current', 'de'));
});

it('saves the category\'s name and slug in the picked locale and keeps the other translations', function () {
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe'], ['en' => 'shoes', 'de' => 'schuhe']);

    pickCategoryContentLocale('de');

    $this->put(route('larasell.admin.categories.update', $shoes), ['name' => 'Schuhwerk', 'slug' => 'schuhwerk', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    $shoes->refresh();

    expect($shoes->name->all())->toBe(['en' => 'Shoes', 'de' => 'Schuhwerk'])
        ->and($shoes->slug->all())->toBe(['en' => 'shoes', 'de' => 'schuhwerk']);
});

it('checks the category slug against the other categories\' slugs in the picked locale', function () {
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe'], ['en' => 'shoes', 'de' => 'schuhe']);
    translatedCategory(['en' => 'Bags', 'de' => 'Taschen'], ['en' => 'bags', 'de' => 'taschen']);

    pickCategoryContentLocale('de');

    // Another category's English slug is free in German, but not its German one.
    $this->put(route('larasell.admin.categories.update', $shoes), ['name' => 'Schuhe', 'slug' => 'bags', 'status' => 'visible'])
        ->assertSessionHasNoErrors();
    $this->put(route('larasell.admin.categories.update', $shoes), ['name' => 'Schuhe', 'slug' => 'taschen', 'status' => 'visible'])
        ->assertSessionHasErrors('slug');
});

it('creates a category in the picked locale', function () {
    pickCategoryContentLocale('de');

    $this->post(route('larasell.admin.categories.store'), ['name' => 'Taschen', 'slug' => 'taschen', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    $category = Category::query()->sole();

    expect($category->name->all())->toBe(['de' => 'Taschen'])
        ->and($category->slug->all())->toBe(['de' => 'taschen']);
});

it('leaves an untranslated category name and slug empty, with what the store shows instead', function () {
    $shoes = translatedCategory(['en' => 'Shoes'], ['en' => 'shoes']);

    pickCategoryContentLocale('de');

    $this->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('category.name', null)
            ->where('category.slug', null)
            ->where('category.fallbackName', 'Shoes')
            ->where('category.fallbackSlug', 'shoes'));
});

it('only edits categories in the default locale without content translations', function () {
    Entitlement::fake(Plan::Free);
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe'], ['en' => 'shoes', 'de' => 'schuhe']);

    $this->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('category.name', 'Shoes')
            ->where('contentLocale.options', [['value' => 'en', 'label' => 'English']]));
});

it('lists the category\'s products by their name in the picked locale', function () {
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe'], ['en' => 'shoes', 'de' => 'schuhe']);
    $boots = Product::create(['name' => ['en' => 'Boots', 'de' => 'Stiefel'], 'slug' => ['en' => 'boots', 'de' => 'stiefel'], 'price' => Price::of(1000)]);
    $socks = Product::create(['name' => ['en' => 'Socks'], 'slug' => ['en' => 'socks'], 'price' => Price::of(500)]);
    $shoes->products()->attach([$boots->id, $socks->id]);

    pickCategoryContentLocale('de');

    $this->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('products.rows.0.name', 'Stiefel')
            // Untranslated, it shows what the store shows.
            ->where('products.rows.1.name', 'Socks'));
});

it('finds products to add by the name the picked locale shows', function () {
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe'], ['en' => 'shoes', 'de' => 'schuhe']);
    Product::create(['name' => ['en' => 'Boots', 'de' => 'Stiefel'], 'slug' => ['en' => 'boots', 'de' => 'stiefel'], 'price' => Price::of(1000)]);
    Product::create(['name' => ['en' => 'Socks'], 'slug' => ['en' => 'socks'], 'price' => Price::of(500)]);

    pickCategoryContentLocale('de');

    $search = fn (string $term) => $this->getJson(route('larasell.admin.categories.available-products.index', [$shoes, 'search' => $term]))
        ->json('products.*.name');

    expect($search(''))->toBe(['Stiefel', 'Socks'])
        ->and($search('stief'))->toBe(['Stiefel'])
        // Shown as Stiefel, so its English name doesn't find it.
        ->and($search('boots'))->toBe([])
        // Untranslated, it's found by the name it shows.
        ->and($search('socks'))->toBe(['Socks']);
});

it('names the categories in the sidebar tree in the picked locale', function () {
    $shoes = translatedCategory(['en' => 'Shoes', 'de' => 'Schuhe'], ['en' => 'shoes', 'de' => 'schuhe']);
    translatedCategory(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel'], $shoes);
    translatedCategory(['en' => 'Bags'], ['en' => 'bags']);

    pickCategoryContentLocale('de');

    $tree = fn (AssertableInertia $page) => $page
        ->where('categories.0.name', 'Schuhe')
        ->where('categories.0.children.0.name', 'Stiefel')
        // Untranslated, it shows what the store shows.
        ->where('categories.1.name', 'Bags');

    $this->get(route('larasell.admin.categories.show', $shoes))->assertInertia($tree);
    $this->get(route('larasell.admin.categories.create'))->assertInertia($tree);
    $this->get(route('larasell.admin.categories.show', 999))->assertNotFound()->assertInertia($tree);
});
