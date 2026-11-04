<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\ContentLocale\AvailableLocales;
use Larasell\Admin\ContentLocale\ContentLocaleResolver;
use Larasell\Admin\Models\Admin;
use Larasell\Chronicle\Facades\Chronicle;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Plan;
use Larasell\Larasell\Facades\Entitlement;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;
use Larasell\Larasell\Translatable;

beforeEach(function () {
    config(['larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN], 'larasell.locale.default' => Locale::ENGLISH]);

    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

/**
 * @param  array<string, string>  $name
 * @param  array<string, string>  $slug
 */
function translatedProduct(array $name, array $slug): Product
{
    return Product::create(['name' => $name, 'slug' => $slug, 'price' => Price::of(1000)]);
}

function switchContentLocale(string $locale, ?string $from = null): void
{
    test()->from($from ?? route('larasell.admin.products.index'))
        ->put(route('larasell.admin.content-locale.update'), ['locale' => $locale])
        ->assertSessionHasNoErrors();
}

it('edits products in the default locale until the admin picks another, and offers the store\'s locales', function () {
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Boots')
            ->where('product.slug', 'boots')
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

it('goes back to the page the locale was picked on', function () {
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);

    $this->from(route('larasell.admin.products.edit', $boots))
        ->put(route('larasell.admin.content-locale.update'), ['locale' => 'de'])
        ->assertRedirect(route('larasell.admin.products.edit', $boots));
});

it('keeps the picked locale across reloads, other products and categories', function () {
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);
    $socks = translatedProduct(['en' => 'Socks', 'de' => 'Socken'], ['en' => 'socks', 'de' => 'socken']);
    $shoes = Category::create(['name' => Translatable::fromString('Shoes'), 'slug' => Translatable::fromString('shoes')]);

    switchContentLocale('de', route('larasell.admin.products.edit', $boots));

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Stiefel')
            ->where('product.slug', 'stiefel')
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
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);

    switchContentLocale('de');

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Stiefel')
            // The options are named in the interface's language.
            ->where('contentLocale.options.1.label', 'German'));

    expect(App::currentLocale())->toBe('en');
});

it('saves the name and slug in the picked locale and keeps the other translations', function () {
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);

    switchContentLocale('de');

    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Gummistiefel', 'slug' => 'gummistiefel', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    $boots->refresh();

    expect($boots->name->all())->toBe(['en' => 'Boots', 'de' => 'Gummistiefel'])
        ->and($boots->slug->all())->toBe(['en' => 'boots', 'de' => 'gummistiefel']);
});

it('checks the slug against the other products\' slugs in the picked locale', function () {
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);
    translatedProduct(['en' => 'Socks', 'de' => 'Socken'], ['en' => 'socks', 'de' => 'socken']);

    switchContentLocale('de');

    // Another product's English slug is free in German, but not its German one.
    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Stiefel', 'slug' => 'socks', 'status' => 'visible'])
        ->assertSessionHasNoErrors();
    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Stiefel', 'slug' => 'socken', 'status' => 'visible'])
        ->assertSessionHasErrors('slug');
});

it('creates a product in the picked locale', function () {
    switchContentLocale('de');

    $this->post(route('larasell.admin.products.store'), ['name' => 'Wollsocken', 'slug' => 'wollsocken', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    $product = Product::query()->sole();

    expect($product->name->all())->toBe(['de' => 'Wollsocken'])
        ->and($product->slug->all())->toBe(['de' => 'wollsocken']);
});

it('leaves an untranslated name and slug empty, with what the store shows instead', function () {
    $boots = translatedProduct(['en' => 'Boots'], ['en' => 'boots']);

    switchContentLocale('de');

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', null)
            ->where('product.slug', null)
            ->where('product.fallbackName', 'Boots')
            ->where('product.fallbackSlug', 'boots'));
});

it('rejects a locale the store does not support', function (mixed $locale) {
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);

    $this->put(route('larasell.admin.content-locale.update'), ['locale' => $locale])
        ->assertSessionHasErrors('locale');

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('contentLocale.current', 'en'));
})->with([
    'missing' => [null],
    'unsupported' => ['fr'],
    'not a locale' => ['klingon!'],
]);

it('falls back to the default locale once the picked one is no longer supported', function () {
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);

    switchContentLocale('de');
    config(['larasell.locale.supported' => [Locale::ENGLISH]]);

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Boots')
            ->where('contentLocale.current', 'en'));
});

it('logs which locale an admin picked and which one each request edits in', function () {
    $chronicle = Chronicle::fake();
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);

    switchContentLocale('de');

    expect($chronicle->added())->toMatchArray(['content_locale.previous' => 'en', 'content_locale.selected' => 'de']);

    $this->get(route('larasell.admin.products.edit', $boots))->assertOk();

    expect($chronicle->added())->toMatchArray(['content_locale.current' => 'de']);
});

it('does not let guests pick a locale', function () {
    auth('larasell-admin')->logout();

    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de'])
        ->assertRedirect(route('larasell.admin.login'));
});

it('offers the locales of the AvailableLocales bound in the container', function () {
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

    $boots = translatedProduct(['en' => 'Boots', 'fr' => 'Bottes'], ['en' => 'boots', 'fr' => 'bottes']);

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
    app()->instance(ContentLocaleResolver::class, new class implements ContentLocaleResolver
    {
        public function resolve(Request $request): Locale
        {
            return Locale::GERMAN;
        }

        public function remember(Request $request, Locale $locale): void {}
    });

    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('product.name', 'Stiefel'));

    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Gummistiefel', 'slug' => 'gummistiefel', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    expect($boots->refresh()->name->all())->toBe(['en' => 'Boots', 'de' => 'Gummistiefel']);
});

it('makes content translations a Pro entitlement', function () {
    expect(Entitlement::all())->toHaveKey('admin.content-translations', true);

    Entitlement::fake(Plan::Free);

    expect(Entitlement::active('admin.content-translations'))->toBeFalse();
});

it('only edits products in the default locale without content translations, and offers no other', function () {
    Entitlement::fake(Plan::Free);
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('product.name', 'Boots')
            ->where('contentLocale.current', 'en')
            ->where('contentLocale.options', [['value' => 'en', 'label' => 'English']]));

    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de'])->assertSessionHasErrors('locale');
});

it('goes back to the default locale once the plan no longer has content translations, and keeps the others', function () {
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);

    switchContentLocale('de');
    Entitlement::fake(Plan::Free);

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('contentLocale.current', 'en'));

    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Rain Boots', 'slug' => 'rain-boots', 'status' => 'visible'])
        ->assertSessionHasNoErrors();

    expect($boots->refresh()->name->all())->toBe(['en' => 'Rain Boots', 'de' => 'Stiefel']);
});

it('also limits the locales of an AvailableLocales an app binds to the default one without content translations', function () {
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

    $this->get(route('larasell.admin.products.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('contentLocale.current', 'fr')
            ->where('contentLocale.options', [['value' => 'fr', 'label' => 'French']]));
});

it('offers the categories by their name in the picked locale', function () {
    $boots = translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);
    Category::create(['name' => ['en' => 'Shoes', 'de' => 'Schuhe'], 'slug' => ['en' => 'shoes', 'de' => 'schuhe']]);
    Category::create(['name' => ['en' => 'Bags'], 'slug' => ['en' => 'bags']]);

    switchContentLocale('de');

    $names = fn (AssertableInertia $page) => $page
        ->where('categories.0.name', 'Schuhe')
        // Untranslated, it shows what the store shows.
        ->where('categories.1.name', 'Bags');

    $this->get(route('larasell.admin.products.edit', $boots))->assertInertia($names);
    $this->get(route('larasell.admin.products.create'))->assertInertia($names);
});

it('lists the products by their name in the picked locale', function () {
    translatedProduct(['en' => 'Boots', 'de' => 'Stiefel'], ['en' => 'boots', 'de' => 'stiefel']);
    translatedProduct(['en' => 'Socks'], ['en' => 'socks']);

    switchContentLocale('de');

    $this->get(route('larasell.admin.products.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.rows.0.name', 'Stiefel')
            // Untranslated, it shows what the store shows.
            ->where('table.rows.1.name', 'Socks'));
});
