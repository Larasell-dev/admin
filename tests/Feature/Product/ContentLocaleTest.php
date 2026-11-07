<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\ContentLocale\Locales\AvailableLocales;
use Larasell\Admin\Platform\ContentLocale\Resolvers\ContentLocaleResolver;
use Larasell\Chronicle\Facades\Chronicle;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Plan;
use Larasell\Larasell\Facades\Entitlement;
use Larasell\Larasell\Price;
use Larasell\Larasell\Translatable;

beforeEach(function () {
    config(['larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN], 'larasell.locale.default' => Locale::ENGLISH]);

    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

/**
 * @param  array<string, string>  $name
 */
function translatedProduct(array $name): Product
{
    return Product::create(['name' => $name, 'price' => Price::of(1000)]);
}

function switchContentLocale(string $locale, ?string $from = null): void
{
    test()->from($from ?? route('larasell.admin.products.index'))
        ->put(route('larasell.admin.content-locale.update'), ['locale' => $locale])
        ->assertSessionHasNoErrors();
}

it('edits products in the default locale until the admin picks another, and offers the store\'s locales', function () {
    // Arrange
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Boots')
            ->where('contentLocale', [
                'current' => 'en',
                'options' => [
                    ['value' => 'en', 'label' => 'English'],
                    ['value' => 'de', 'label' => 'German'],
                ],
                'switchUrl' => route('larasell.admin.content-locale.update'),
            ]));

    $this->get(route('larasell.admin.products.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('contentLocale.current', 'en'));
});

it('offers the content locale on every page, so it can be switched from anywhere', function () {
    // Arrange
    switchContentLocale('de');

    // Act & Assert
    foreach ([route('larasell.admin.home'), route('larasell.admin.orders.index'), route('larasell.admin.profile')] as $url) {
        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('contentLocale', [
                'current' => 'de',
                'options' => [
                    ['value' => 'en', 'label' => 'English'],
                    ['value' => 'de', 'label' => 'German'],
                ],
                'switchUrl' => route('larasell.admin.content-locale.update'),
            ]));
    }
});

it('offers no content locale to guests', function () {
    // Arrange
    auth('larasell-admin')->logout();

    // Act & Assert
    $this->get(route('larasell.admin.login'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('contentLocale', null));
});

it('goes back to the page the locale was picked on', function () {
    // Arrange
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    // Act & Assert
    $this->from(route('larasell.admin.products.edit', $boots))
        ->put(route('larasell.admin.content-locale.update'), ['locale' => 'de'])
        ->assertRedirect(route('larasell.admin.products.edit', $boots));
});

it('keeps the picked locale across reloads, other products and categories', function () {
    // Arrange
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);
    $socks = translatedProduct(['en' => 'Socks', 'de' => 'Socken']);
    $shoes = Category::create(['name' => Translatable::fromString('Shoes')]);

    switchContentLocale('de', route('larasell.admin.products.edit', $boots));

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Stiefel')
            ->where('contentLocale.current', 'de'));

    $this->get(route('larasell.admin.categories.show', $shoes))->assertOk();

    $this->get(route('larasell.admin.products.edit', $socks))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Socken')
            ->where('contentLocale.current', 'de'));

    $this->get(route('larasell.admin.products.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('contentLocale.current', 'de'));
});

it('changes the language of the products, not of the interface', function () {
    // Arrange
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    switchContentLocale('de');

    // Act
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Stiefel')
            // The options are named in the interface's language.
            ->where('contentLocale.options.1.label', 'German'));

    // Assert
    // Still the interface language the admin picked, English by default.
    expect(App::currentLocale())->toBe('en-US');
});

it('saves the name in the picked locale and keeps the other translations', function () {
    // Arrange
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    switchContentLocale('de');

    // Act
    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Gummistiefel', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    // Assert
    $boots->refresh();

    expect($boots->name->all())->toBe(['en' => 'Boots', 'de' => 'Gummistiefel']);
});

it('creates a product in the picked locale', function () {
    // Arrange
    switchContentLocale('de');

    // Act
    $this->post(route('larasell.admin.products.store'), ['name' => 'Wollsocken', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    // Assert
    $product = Product::query()->sole();

    expect($product->name->all())->toBe(['de' => 'Wollsocken']);
});

it('leaves an untranslated name empty, with what the store shows instead', function () {
    // Arrange
    $boots = translatedProduct(['en' => 'Boots']);

    switchContentLocale('de');

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', null)
            ->where('product.fallbackName', 'Boots'));
});

it('rejects a locale the store does not support', function (mixed $locale) {
    // Arrange
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    // Act
    $this->put(route('larasell.admin.content-locale.update'), ['locale' => $locale])
        ->assertSessionHasErrors('locale');

    // Assert
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('contentLocale.current', 'en'));
})->with([
    'missing' => [null],
    'unsupported' => ['fr'],
    'not a locale' => ['klingon!'],
]);

it('falls back to the default locale once the picked one is no longer supported', function () {
    // Arrange
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    switchContentLocale('de');
    config(['larasell.locale.supported' => [Locale::ENGLISH]]);

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Boots')
            ->where('contentLocale.current', 'en'));
});

it('logs which locale an admin picked and which one each request edits in', function () {
    // Arrange
    $chronicle = Chronicle::fake();
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    // Act
    switchContentLocale('de');

    // Assert
    expect($chronicle->added())->toMatchArray(['content_locale.previous' => 'en', 'content_locale.selected' => 'de']);

    $this->get(route('larasell.admin.products.edit', $boots))->assertOk();

    expect($chronicle->added())->toMatchArray(['content_locale.current' => 'de']);
});

it('does not let guests pick a locale', function () {
    // Arrange
    auth('larasell-admin')->logout();

    // Act & Assert
    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de'])
        ->assertRedirect(route('larasell.admin.login'));
});

it('offers the locales of the AvailableLocales bound in the container', function () {
    // Arrange
    // Like a sales channel that sells in English and French while the store config says German.
    app()->instance(AvailableLocales::class, new class implements AvailableLocales
    {
        public function all(): array
        {
            return [Locale::FRENCH, Locale::ENGLISH];
        }

        public function default(): Locale
        {
            return Locale::FRENCH;
        }
    });

    $boots = translatedProduct(['en' => 'Boots', 'fr' => 'Bottes']);

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Bottes')
            ->where('contentLocale.current', 'fr')
            ->where('contentLocale.options', [
                ['value' => 'fr', 'label' => 'French'],
                ['value' => 'en', 'label' => 'English'],
            ]));

    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de'])->assertSessionHasErrors('locale');
    switchContentLocale('en');
});

it('edits products in the locale of the ContentLocaleResolver bound in the container', function () {
    // Arrange
    app()->instance(ContentLocaleResolver::class, new class implements ContentLocaleResolver
    {
        public function resolve(Request $request): Locale
        {
            return Locale::GERMAN;
        }

        public function remember(Request $request, Locale $locale): void {}
    });

    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('product.name', 'Stiefel'));

    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Gummistiefel', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    expect($boots->refresh()->name->all())->toBe(['en' => 'Boots', 'de' => 'Gummistiefel']);
});

it('makes content translations a Pro entitlement', function () {
    // Act & Assert
    expect(Entitlement::all())->toHaveKey('admin.content-translations', true);

    Entitlement::fake(Plan::Free);

    expect(Entitlement::active('admin.content-translations'))->toBeFalse();
});

it('only edits products in the default locale without content translations, and offers no other', function () {
    // Arrange
    Entitlement::fake(Plan::Free);
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Boots')
            ->where('contentLocale.current', 'en')
            ->where('contentLocale.options', [['value' => 'en', 'label' => 'English']]));

    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('contentLocale.options', [['value' => 'en', 'label' => 'English']]));

    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de'])->assertSessionHasErrors('locale');
});

it('goes back to the default locale once the plan no longer has content translations, and keeps the others', function () {
    // Arrange
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);

    switchContentLocale('de');
    Entitlement::fake(Plan::Free);

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('contentLocale.current', 'en'));

    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Rain Boots', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    expect($boots->refresh()->name->all())->toBe(['en' => 'Rain Boots', 'de' => 'Stiefel']);
});

it('also limits the locales of an AvailableLocales an app binds to the default one without content translations', function () {
    // Arrange
    // Like a sales channel's locales: bound after the panel, so its strategy can't skip the plan.
    app()->bind(AvailableLocales::class, fn () => new class implements AvailableLocales
    {
        public function all(): array
        {
            return [Locale::FRENCH, Locale::ENGLISH];
        }

        public function default(): Locale
        {
            return Locale::FRENCH;
        }
    });
    Entitlement::fake(Plan::Free);

    // Act & Assert
    $this->get(route('larasell.admin.products.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('contentLocale.current', 'fr')
            ->where('contentLocale.options', [['value' => 'fr', 'label' => 'French']]));
});

it('offers the categories by their name in the picked locale', function () {
    // Arrange
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);
    Category::create(['name' => ['en' => 'Shoes', 'de' => 'Schuhe']]);
    Category::create(['name' => ['en' => 'Bags']]);

    switchContentLocale('de');

    $names = fn (AssertableInertia $page) => $page
        ->where('categories.0.name', 'Schuhe')
        // Untranslated, it shows what the store shows.
        ->where('categories.1.name', 'Bags');

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $boots))->assertInertia($names);
    $this->get(route('larasell.admin.products.create'))->assertInertia($names);
});

it('lists the products by their name in the picked locale', function () {
    // Arrange
    translatedProduct(['en' => 'Boots', 'de' => 'Stiefel']);
    translatedProduct(['en' => 'Socks']);

    switchContentLocale('de');

    // Act & Assert
    $this->get(route('larasell.admin.products.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.rows.0.name', 'Stiefel')
            // Untranslated, it shows what the store shows.
            ->where('table.rows.1.name', 'Socks'));
});
