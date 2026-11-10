<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\InterfaceLocale\Resolvers\CookieInterfaceLocaleResolver;
use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Models\Order;
use Larasell\Larasell\Price;
use Larasell\Larasell\SalesChannels\Actions\CreateSalesChannel;
use Larasell\Larasell\SalesChannels\Events\SalesChannelCreated;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\SalesChannels\SalesContext;
use Larasell\Larasell\Settings\CurrencySettings;
use Larasell\Larasell\Storefront\Models\EntryPoint;

beforeEach(function () {
    config(['larasell.locale.supported' => ['en', 'de']]);
    $this->admin = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $this->actingAs($this->admin, 'larasell-admin');
});

/** @return array<string, mixed> */
function channelInput(array $overrides = []): array
{
    return [
        'key' => 'b2b',
        'name' => 'Wholesale',
        'type' => 'storefront',
        'locales' => ['de', 'en'],
        'currencies' => ['USD'],
        'default_locale' => 'en',
        'default_currency' => 'USD',
        'active' => '1',
        ...$overrides,
    ];
}

it('lists the sales channels by name in the sidebar, once', function () {
    // Act & Assert
    SalesChannel::query()->whereKey(1)->update(['name' => 'Webshop']);
    app(CreateSalesChannel::class)->handle(['key' => 'b2b', 'name' => 'B2B', 'type' => 'storefront', 'locales' => ['en'], 'currencies' => ['USD']]);

    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('salesChannels.createUrl', route('larasell.admin.sales-channels.create'))
            ->where('salesChannels.channels.0.name', 'B2B')
            ->where('salesChannels.channels.1.name', 'Webshop')
            ->where('salesChannels.channels.1.url', route('larasell.admin.sales-channels.edit', 1)));

    // The browser says it already has them, so the next page leaves them out.
    $this->get(route('larasell.admin.home'), ['X-Inertia' => 'true', 'X-Inertia-Except-Once-Props' => 'salesChannels'])
        ->assertJsonMissingPath('props.salesChannels');
});

it('creates a sales channel through the core action and sends the sidebar again', function () {
    // Act & Assert
    Event::fake([SalesChannelCreated::class]);

    $response = $this->post(route('larasell.admin.sales-channels.store'), channelInput());

    $channel = SalesChannel::query()->where('key', 'b2b')->firstOrFail();
    $response->assertRedirect(route('larasell.admin.sales-channels.edit', $channel));
    expect($channel->name)->toBe('Wholesale')
        ->and(array_map(fn ($locale) => $locale->value, $channel->locales()))->toBe(['de', 'en'])
        ->and($channel->default_locale->value)->toBe('en');
    Event::assertDispatched(SalesChannelCreated::class);

    $this->get(route('larasell.admin.home'), ['X-Inertia' => 'true', 'X-Inertia-Except-Once-Props' => 'salesChannels'])
        ->assertJsonPath('props.salesChannels.channels.0.name', 'Laravel')
        ->assertJsonPath('props.salesChannels.channels.1.name', 'Wholesale');
});

it('refuses a taken key, an unsupported locale and a default the channel does not sell in', function () {
    // Act & Assert
    $this->post(route('larasell.admin.sales-channels.store'), channelInput(['key' => 'default', 'locales' => ['fr'], 'default_currency' => 'EUR']))
        ->assertSessionHasErrors(['key', 'locales.0', 'default_currency']);

    expect(SalesChannel::query()->count())->toBe(1);
});

it('updates a sales channel but keeps its key', function () {
    // Act & Assert
    $this->put(route('larasell.admin.sales-channels.update', 1), [...channelInput(['key' => 'renamed', 'name' => 'Webshop', 'active' => '0'])])
        ->assertRedirect(route('larasell.admin.sales-channels.edit', 1));

    $channel = SalesChannel::query()->findOrFail(1);
    expect($channel->key)->toBe('default')
        ->and($channel->name)->toBe('Webshop')
        ->and($channel->active)->toBeFalse();
});

it('deletes a sales channel nobody bought in once its name is typed, but not the last one or one with orders', function () {
    // Act & Assert
    $this->post(route('larasell.admin.sales-channels.store'), channelInput());
    $b2b = SalesChannel::query()->where('key', 'b2b')->firstOrFail();

    // The key, or the name in other case, doesn't confirm it.
    $this->delete(route('larasell.admin.sales-channels.destroy', $b2b), ['confirmation' => 'b2b'])->assertSessionHasErrors('confirmation');
    $this->delete(route('larasell.admin.sales-channels.destroy', $b2b), ['confirmation' => 'wholesale'])->assertSessionHasErrors('confirmation');
    expect(SalesChannel::query()->whereKey($b2b->id)->exists())->toBeTrue();

    $this->delete(route('larasell.admin.sales-channels.destroy', $b2b), ['confirmation' => 'Wholesale'])->assertRedirect(route('larasell.admin.home'));
    expect(SalesChannel::query()->whereKey($b2b->id)->exists())->toBeFalse();

    $this->delete(route('larasell.admin.sales-channels.destroy', 1), ['confirmation' => 'Laravel'])->assertSessionHasErrors('channel');

    $this->post(route('larasell.admin.sales-channels.store'), channelInput());
    $b2b = SalesChannel::query()->where('key', 'b2b')->firstOrFail();
    Order::createIn(SalesContext::for('b2b', 'en'), ['number' => '2026-000123', 'customer_email' => 'ada@example.com', 'customer_name' => 'Ada Lovelace', 'currency' => 'USD', 'subtotal' => Price::of(0), 'total' => Price::of(0)]);

    $this->delete(route('larasell.admin.sales-channels.destroy', $b2b), ['confirmation' => 'Wholesale'])->assertSessionHasErrors('channel');
    expect(SalesChannel::query()->whereKey($b2b->id)->exists())->toBeTrue();
});

it('names the currencies and languages in the interface language', function () {
    // Act & Assert
    app(CurrencySettings::class)->save([Currency::USD, Currency::EUR]);

    $this->withCookie(CookieInterfaceLocaleResolver::COOKIE, 'de-DE')
        ->get(route('larasell.admin.sales-channels.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('currencies', [['value' => 'USD', 'label' => 'US-Dollar'], ['value' => 'EUR', 'label' => 'Euro']])
            ->where('locales.1', ['value' => 'de', 'label' => 'Deutsch']));
});

it('adds domains to a storefront through the core action and hands primary on when one is deleted', function () {
    // Act & Assert
    EntryPoint::query()->delete(); // The default channel's own, on app.url.
    $edit = route('larasell.admin.sales-channels.edit', 1);
    $add = route('larasell.admin.sales-channels.entry-points.store', 1);

    $this->post($add, ['url' => 'https://Shop.example/de/', 'locale' => 'en', 'default_currency' => ''])->assertRedirect($edit);
    $this->post($add, ['url' => 'https://shop.example/en', 'locale' => 'en', 'default_currency' => 'USD'])->assertRedirect($edit);

    $this->get($edit)->assertInertia(fn (AssertableInertia $page) => $page
        ->has('entryPoints.entryPoints', 2)
        ->where('entryPoints.entryPoints.0.url', 'https://shop.example/de')
        ->where('entryPoints.entryPoints.0.primary', true)
        ->where('entryPoints.entryPoints.0.defaultCurrency', null)
        ->where('entryPoints.entryPoints.1.primary', false)
        ->where('entryPoints.entryPoints.1.defaultCurrency', 'USD'));

    $first = EntryPoint::query()->orderBy('id')->firstOrFail();
    $destroy = route('larasell.admin.sales-channels.entry-points.destroy', [1, $first->id]);
    $this->delete($destroy, ['confirmation' => 'shop.example'])->assertSessionHasErrors(['confirmation']);
    $this->delete($destroy, ['confirmation' => 'shop.example/de'])->assertRedirect($edit);

    $this->get($edit)->assertInertia(fn (AssertableInertia $page) => $page
        ->has('entryPoints.entryPoints', 1)
        ->where('entryPoints.entryPoints.0.url', 'https://shop.example/en')
        ->where('entryPoints.entryPoints.0.primary', true));
});

it('pages the domains by the domains query parameters', function () {
    // Arrange
    EntryPoint::query()->delete();
    $add = route('larasell.admin.sales-channels.entry-points.store', 1);
    foreach (range(1, 30) as $i) {
        $this->post($add, ['url' => "https://shop{$i}.example", 'locale' => 'en']);
    }
    $edit = route('larasell.admin.sales-channels.edit', 1);

    // Act & Assert
    $this->get($edit)->assertInertia(fn (AssertableInertia $page) => $page
        ->where('entryPoints.page', 1)
        ->where('entryPoints.pageSize', 10)
        ->where('entryPoints.total', 30)
        ->has('entryPoints.entryPoints', 10)
        // The primary domain comes first.
        ->where('entryPoints.entryPoints.0.url', 'https://shop1.example'));

    $this->get($edit.'?domains[page]=2&domains[size]=25')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('entryPoints.page', 2)
        ->where('entryPoints.pageSize', 25)
        ->has('entryPoints.entryPoints', 5)
        ->where('entryPoints.entryPoints.4.url', 'https://shop30.example'));

    // A page past the end shows the last page; a size that isn't offered falls back to 10.
    $this->get($edit.'?domains[page]=9&domains[size]=7')->assertInertia(fn (AssertableInertia $page) => $page
        ->where('entryPoints.page', 3)
        ->where('entryPoints.pageSize', 10)
        ->has('entryPoints.entryPoints', 10));
});

it('refuses a taken URL, a language the channel does not sell in, and dropping a language a domain serves', function () {
    // Act & Assert
    EntryPoint::query()->delete();
    $add = route('larasell.admin.sales-channels.entry-points.store', 1);
    $this->post($add, ['url' => 'https://shop.example', 'locale' => 'en'])->assertSessionHasNoErrors();

    $this->post($add, ['url' => 'https://SHOP.example/', 'locale' => 'de', 'default_currency' => 'JPY'])
        ->assertSessionHasErrors(['url', 'locale', 'default_currency']);
    $this->post($add, ['url' => 'shop.example', 'locale' => 'en'])
        ->assertSessionHasErrors(['url']);

    $this->put(route('larasell.admin.sales-channels.update', 1), channelInput(['name' => 'Laravel', 'locales' => ['de'], 'default_locale' => 'de']))
        ->assertSessionHasErrors(['channel']);

    expect(EntryPoint::query()->count())->toBe(1);
});
