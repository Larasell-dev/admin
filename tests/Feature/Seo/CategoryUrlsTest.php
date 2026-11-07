<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Catalog\Models\Category;
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
function categoryUrlsInput(string $name, array $paths, array $input = []): array
{
    return ['name' => $name, 'status' => 'visible', ...$input, 'sections' => ['seo' => ['urls' => array_map(fn (string $path) => ['path' => $path], $paths)]]];
}

it('gives a category a URL per channel in the content language, custom where one is typed', function () {
    // Act
    $this->post(route('larasell.admin.categories.store'), categoryUrlsInput('Shoes', [1 => '', $this->europe => '/Footwear/']))
        ->assertSessionHasNoErrors();

    // Assert
    $shoes = Category::query()->sole();

    $this->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.0.key', 'seo')
            ->where('sections.0.props.urls', [
                ['channelId' => $this->europe, 'channel' => 'Europe', 'baseUrl' => 'https://shop.de/en/', 'customPath' => 'footwear', 'automaticPath' => 'shoes'],
                ['channelId' => 1, 'channel' => 'Laravel', 'baseUrl' => rtrim(config('app.url'), '/').'/', 'customPath' => null, 'automaticPath' => 'shoes'],
            ]));

    // The German page only has the channel with a German domain.
    $this->from(route('larasell.admin.categories.index'))->put(route('larasell.admin.content-locale.update'), ['locale' => 'de']);

    $this->get(route('larasell.admin.categories.show', $shoes))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('sections.0.props.urls', 1)
            ->where('sections.0.props.urls.0.baseUrl', 'https://shop.de/')
            ->where('sections.0.props.urls.0.customPath', null));
});

it('shows the URLs section on the new category page, without URLs yet', function () {
    // Act & Assert
    $this->get(route('larasell.admin.categories.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.0.key', 'seo')
            ->where('sections.0.props.urls.1.automaticPath', null));
});

it('makes a URL from the name again once its field is emptied, below its parent', function () {
    // Arrange
    $this->post(route('larasell.admin.categories.store'), categoryUrlsInput('Shoes', [$this->europe => 'footwear']));
    $shoes = Category::query()->sole();
    $this->post(route('larasell.admin.categories.store'), categoryUrlsInput('Boots', [$this->europe => 'all-boots'], ['parent_id' => $shoes->id]));
    $boots = Category::query()->latest('id')->firstOrFail();

    // Act
    $this->put(route('larasell.admin.categories.update', $boots), categoryUrlsInput('Winter Boots', [$this->europe => '']))
        ->assertSessionHasNoErrors();

    // Assert
    expect(app(SeoUrlManager::class)->canonical($boots->fresh(), SalesContext::for('europe', 'en')))
        ->path->toBe('footwear/winter-boots')
        ->is_custom->toBeFalse();
});

it('refuses a URL another page uses, and saves nothing of the category', function () {
    // Arrange
    $this->post(route('larasell.admin.categories.store'), categoryUrlsInput('Shoes', [1 => '']));
    $this->post(route('larasell.admin.categories.store'), categoryUrlsInput('Socks', [1 => '']));
    $socks = Category::query()->latest('id')->firstOrFail();

    // Act & Assert
    $this->put(route('larasell.admin.categories.update', $socks), categoryUrlsInput('Wool Socks', [1 => 'shoes']))
        ->assertSessionHasErrors(['sections.seo.urls.1.path' => 'Another page already uses this URL.']);

    expect($socks->fresh()->name->get())->toBe('Socks');
});

it('creates no category when its URL is refused', function () {
    // Arrange
    $this->post(route('larasell.admin.categories.store'), categoryUrlsInput('Shoes', [1 => '']));

    // Act & Assert
    $this->post(route('larasell.admin.categories.store'), categoryUrlsInput('Socks', [1 => 'shoes']))
        ->assertSessionHasErrors(['sections.seo.urls.1.path' => 'Another page already uses this URL.']);

    expect(Category::query()->count())->toBe(1);
});
