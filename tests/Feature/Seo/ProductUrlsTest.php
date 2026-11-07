<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\SalesChannels\Actions\CreateSalesChannel;
use Larasell\Larasell\SalesChannels\SalesChannelId;
use Larasell\Larasell\SalesChannels\SalesContext;
use Larasell\Larasell\Seo\SeoUrlManager;
use Larasell\Larasell\Settings\CurrencySettings;
use Larasell\Larasell\Storefront\Actions\AddEntryPoint;

beforeEach(function () {
    config(['larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN], 'larasell.locale.default' => Locale::ENGLISH, 'app.name' => 'Laravel']);
    app(CurrencySettings::class)->save([Currency::USD, Currency::EUR]);
    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');

    // Next to the default channel "Laravel", which sells in English on app.url.
    $europe = app(CreateSalesChannel::class)->handle(['key' => 'europe', 'name' => 'Europe', 'type' => 'storefront', 'locales' => ['de', 'en'], 'currencies' => ['EUR']]);
    app(AddEntryPoint::class)->handle(SalesChannelId::of($europe), ['url' => 'https://shop.de', 'locale' => 'de']);
    app(AddEntryPoint::class)->handle(SalesChannelId::of($europe), ['url' => 'https://shop.de/en', 'locale' => 'en']);
    $this->europe = $europe->id;
});

/** @return array<string, mixed> */
function productUrlsInput(string $name, array $paths): array
{
    return ['name' => $name, 'status' => 'visible', 'sections' => ['seo' => ['urls' => array_map(fn (string $path) => ['path' => $path], $paths)]]];
}

it('gives a product a URL per channel in the content language, custom where one is typed', function () {
    // Act
    $this->post(route('larasell.admin.products.store'), productUrlsInput('Boots', [1 => '', $this->europe => '/Winter-Boots/']))
        ->assertSessionHasNoErrors();

    // Assert
    $boots = Product::query()->sole();

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.0.key', 'seo')
            ->where('sections.0.props.urls', fn ($urls) => collect($urls)->map(fn ($url) => collect($url)->except('history')->all())->all() === [
                ['channelId' => $this->europe, 'channel' => 'Europe', 'baseUrl' => 'https://shop.de/en/', 'customPath' => 'winter-boots', 'automaticPath' => 'p/boots'],
                ['channelId' => 1, 'channel' => 'Laravel', 'baseUrl' => rtrim(config('app.url'), '/').'/', 'customPath' => null, 'automaticPath' => 'p/boots'],
            ]));

    // The German page only has the channel with a German domain.
    $this->from(route('larasell.admin.products.index'))->put(route('larasell.admin.content-locale.update'), ['locale' => 'de']);

    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sections.0.props.urls', 1)
            ->where('sections.0.props.urls.0.baseUrl', 'https://shop.de/')
            ->where('sections.0.props.urls.0.customPath', null));
});

it('makes a URL from the name again once its field is emptied', function () {
    // Arrange
    $this->post(route('larasell.admin.products.store'), productUrlsInput('Boots', [$this->europe => 'winter-boots']));
    $boots = Product::query()->sole();

    // Act
    $this->put(route('larasell.admin.products.update', $boots), productUrlsInput('Rain Boots', [$this->europe => '']))
        ->assertSessionHasNoErrors();

    // Assert
    expect(app(SeoUrlManager::class)->canonical($boots, SalesContext::for('europe', 'en')))
        ->path->toBe('p/rain-boots')
        ->is_custom->toBeFalse();
});

it('refuses a URL another page uses, and saves nothing of the product', function () {
    // Arrange
    $this->post(route('larasell.admin.products.store'), productUrlsInput('Boots', [1 => '']));
    $this->post(route('larasell.admin.products.store'), productUrlsInput('Socks', [1 => '']));
    $socks = Product::query()->latest('id')->firstOrFail();

    // Act & Assert
    $this->put(route('larasell.admin.products.update', $socks), productUrlsInput('Wool Socks', [1 => 'p/boots']))
        ->assertSessionHasErrors(['sections.seo.urls.1.path' => 'Another page already uses this URL.']);

    expect($socks->fresh()->name->get())->toBe('Socks');
});

it('saves a custom path longer than 255 characters, since the core takes paths of any length', function () {
    // Arrange
    $path = 'sale/'.str_repeat('a', 300);

    // Act
    $this->post(route('larasell.admin.products.store'), productUrlsInput('Boots', [$this->europe => $path]))
        ->assertSessionHasNoErrors();

    // Assert
    expect(app(SeoUrlManager::class)->canonical(Product::query()->sole(), SalesContext::for('europe', 'en'))?->path)->toBe($path);
});
