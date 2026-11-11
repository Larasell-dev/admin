<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Audit\Facades\Audit;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\SalesChannels\Actions\CreateSalesChannel;
use Larasell\Larasell\SalesChannels\SalesChannelId;
use Larasell\Larasell\SalesChannels\SalesContext;
use Larasell\Larasell\Seo\AuditRecords\SeoUrlRestored;
use Larasell\Larasell\Seo\Models\SeoUrl;
use Larasell\Larasell\Seo\SeoUrlManager;
use Larasell\Larasell\Seo\SeoUrlStatus;
use Larasell\Larasell\Settings\CurrencySettings;
use Larasell\Larasell\Storefront\Actions\AddEntryPoint;

beforeEach(function () {
    config(['larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN], 'larasell.locale.default' => Locale::ENGLISH, 'app.name' => 'Laravel']);
    app(CurrencySettings::class)->save([Currency::USD, Currency::EUR]);
    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');

    // Next to the default channel "Laravel", which sells in English on app.url.
    $europe = app(CreateSalesChannel::class)->handle(['key' => 'europe', 'name' => 'Europe', 'type' => 'storefront', 'locales' => ['de', 'en'], 'currencies' => ['EUR']], origin());
    app(AddEntryPoint::class)->handle(SalesChannelId::of($europe), ['url' => 'https://shop.de/en', 'locale' => 'en'], origin());
    $this->europe = $europe->id;
});

/** @return array<string, mixed> */
function urlHistoryInput(string $name, array $paths = []): array
{
    return ['name' => $name, 'status' => 'visible', 'sections' => ['seo' => ['urls' => array_map(fn (string $path) => ['path' => $path], $paths)]]];
}

/** A visible product renamed twice, so each channel has two old URLs that redirect. */
function renamedBoots(): Product
{
    test()->post(route('larasell.admin.products.store'), urlHistoryInput('Boots'));
    $boots = Product::query()->sole();
    test()->travel(1)->minute();
    test()->put(route('larasell.admin.products.update', $boots), urlHistoryInput('Rain Boots'));
    test()->travel(1)->minute();
    test()->put(route('larasell.admin.products.update', $boots), urlHistoryInput('Snow Boots'));

    return $boots;
}

function oldUrl(Product $product, string $channel, string $path): SeoUrl
{
    return SeoUrl::query()
        ->where('resource_type', 'product')
        ->where('resource_id', $product->id)
        ->where('sales_channel_id', SalesContext::for($channel, 'en')->channelId->value)
        ->where('path', $path)
        ->sole();
}

it('lists the old URLs of each channel below the current one, newest first', function () {
    // Arrange
    $boots = renamedBoots();
    // Another product's old URL is not this page's history.
    $this->post(route('larasell.admin.products.store'), urlHistoryInput('Socks'));
    $socks = Product::query()->latest('id')->firstOrFail();
    $this->put(route('larasell.admin.products.update', $socks), urlHistoryInput('Wool Socks'));

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.0.props.urls.0.channel', 'Europe')
            ->where('sections.0.props.urls.0.automaticPath', 'p/snow-boots')
            ->where('sections.0.props.urls.0.history.urls', array_map(fn (SeoUrl $old) => [
                'id' => $old->id,
                'path' => $old->path,
                'removeUrl' => route('larasell.admin.seo-urls.destroy', $old->id),
                'restoreUrl' => route('larasell.admin.seo-urls.restore', $old->id),
            ], [oldUrl($boots, 'europe', 'p/rain-boots'), oldUrl($boots, 'europe', 'p/boots')]))
            ->where('sections.0.props.urls.1.channel', 'Laravel')
            ->where('sections.0.props.urls.1.history.urls.0.id', oldUrl($boots, 'default', 'p/rain-boots')->id)
            ->has('sections.0.props.urls.1.history.urls', 2)
            ->where('sections.0.props.urls.1.history.total', 2));
});

it('pages each channel\'s old URLs on its own, by 10 or a size from the query string', function () {
    // Arrange
    $boots = renamedBoots();
    $manager = app(SeoUrlManager::class);
    $europe = SalesContext::for('europe', 'en');
    foreach (range(1, 28) as $i) {
        $manager->setCustom($boots, $europe, "sale/boots-{$i}", origin());
    }
    $manager->resetToAutomatic($boots, $europe, origin());
    // 30 for Europe: the 2 renamed-away URLs and the 28 custom ones; the reset makes "p/snow-boots" current again.
    $edit = route('larasell.admin.products.edit', $boots);

    // Act & Assert
    $this->get($edit)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.0.props.urls.0.history.page', 1)
            ->where('sections.0.props.urls.0.history.pageSize', 10)
            ->where('sections.0.props.urls.0.history.total', 30)
            ->has('sections.0.props.urls.0.history.urls', 10)
            ->where('sections.0.props.urls.0.history.urls.0.path', 'sale/boots-28'));

    $this->get($edit.'?old-urls['.$this->europe.'][page]=2&old-urls['.$this->europe.'][size]=25')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.0.props.urls.0.history.page', 2)
            ->where('sections.0.props.urls.0.history.pageSize', 25)
            ->has('sections.0.props.urls.0.history.urls', 5)
            // Newest first, so the last page ends with the very first URL.
            ->where('sections.0.props.urls.0.history.urls.4.path', 'p/boots')
            // The other channel's list keeps its own page and size.
            ->where('sections.0.props.urls.1.history.page', 1)
            ->where('sections.0.props.urls.1.history.pageSize', 10)
            ->has('sections.0.props.urls.1.history.urls', 2));

    // A page past the end, e.g. after removing the last URL on it, shows the last page;
    // a size that isn't offered falls back to 10.
    $this->get($edit.'?old-urls['.$this->europe.'][page]=9&old-urls['.$this->europe.'][size]=7')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.0.props.urls.0.history.page', 3)
            ->where('sections.0.props.urls.0.history.pageSize', 10)
            ->has('sections.0.props.urls.0.history.urls', 10));
});

it('has no old URLs for a product that was renamed before it was ever visible', function () {
    // Arrange
    $this->post(route('larasell.admin.products.store'), ['name' => 'Boots', 'status' => 'hidden']);
    $boots = Product::query()->sole();
    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Rain Boots', 'status' => 'hidden']);

    // Act & Assert
    $this->get(route('larasell.admin.products.edit', $boots))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sections.0.props.urls.0.history', ['urls' => [], 'page' => 1, 'pageSize' => 10, 'total' => 0])
            ->where('sections.0.props.urls.1.history.urls', []));
});

it('removes an old URL, so it no longer redirects, and keeps the others', function () {
    // Arrange
    $boots = renamedBoots();
    $old = oldUrl($boots, 'europe', 'p/boots');

    // Act
    $this->from(route('larasell.admin.products.edit', $boots))
        ->delete(route('larasell.admin.seo-urls.destroy', $old), ['confirmation' => 'Delete redirect'])
        ->assertRedirect(route('larasell.admin.products.edit', $boots));

    // Assert
    expect(SeoUrl::query()->find($old->id))->toBeNull()
        ->and(oldUrl($boots, 'default', 'p/boots')->status)->toBe(SeoUrlStatus::Historic)
        ->and(oldUrl($boots, 'europe', 'p/rain-boots')->status)->toBe(SeoUrlStatus::Historic);
});

it('keeps an old URL unless the admin typed the confirmation', function () {
    // Arrange
    $boots = renamedBoots();
    $old = oldUrl($boots, 'europe', 'p/boots');

    // Act & Assert
    $this->delete(route('larasell.admin.seo-urls.destroy', $old), ['confirmation' => 'delete redirect'])
        ->assertSessionHasErrors(['confirmation' => 'Type "Delete redirect" to confirm.']);

    expect($old->fresh()->status)->toBe(SeoUrlStatus::Historic);
});

it('refuses to remove a current URL', function () {
    // Arrange
    $boots = renamedBoots();
    $current = app(SeoUrlManager::class)->canonical($boots, SalesContext::for('europe', 'en'));

    // Act & Assert
    $this->delete(route('larasell.admin.seo-urls.destroy', $current), ['confirmation' => 'Delete redirect'])->assertNotFound();

    expect($current->fresh()->status)->toBe(SeoUrlStatus::Canonical);
});

it('makes an old URL current again in its channel, keeping it through renames', function () {
    // Arrange
    $boots = renamedBoots();
    Audit::fake([SeoUrlRestored::class]);

    // Act
    $this->from(route('larasell.admin.products.edit', $boots))
        ->post(route('larasell.admin.seo-urls.restore', oldUrl($boots, 'europe', 'p/boots')))
        ->assertRedirect(route('larasell.admin.products.edit', $boots));

    // Assert
    $europe = SalesContext::for('europe', 'en');
    expect(app(SeoUrlManager::class)->canonical($boots, $europe))
        ->path->toBe('p/boots')
        // Not the name's path any more, so it must be custom or the next save would replace it.
        ->is_custom->toBeTrue()
        ->and(oldUrl($boots, 'europe', 'p/snow-boots')->status)->toBe(SeoUrlStatus::Historic)
        ->and(app(SeoUrlManager::class)->canonical($boots, SalesContext::for('default', 'en'))->path)->toBe('p/snow-boots');

    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Winter Boots', 'status' => 'visible']);
    expect(app(SeoUrlManager::class)->canonical($boots, $europe)->path)->toBe('p/boots');
    Audit::assertRecorded(SeoUrlRestored::class, fn (SeoUrlRestored $record, Origin $origin) => $origin->actor instanceof Admin && $record->properties()['path'] === 'p/boots');
});

it('refuses to make a deleted page\'s URL current', function () {
    // Arrange
    $boots = renamedBoots();
    $old = oldUrl($boots, 'europe', 'p/boots');
    app(SeoUrlManager::class)->markGone($boots);

    // Act & Assert
    $this->post(route('larasell.admin.seo-urls.restore', $old))->assertNotFound();

    expect($old->fresh()->status)->toBe(SeoUrlStatus::Gone);
});
