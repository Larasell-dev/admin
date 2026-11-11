<?php

declare(strict_types=1);

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Models\Cart;
use Larasell\Larasell\SalesChannels\Actions\CreateSalesChannel;
use Larasell\Larasell\SalesChannels\Actions\UpdateSalesChannel;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\SalesChannels\SalesChannelId;
use Larasell\Larasell\SalesChannels\SalesContext;

/** What was broadcast, as [channels, event, socket left out]. */
function broadcasts(): ArrayObject
{
    return app('test.broadcasts');
}

beforeEach(function () {
    config(['larasell.locale.supported' => ['en', 'de']]);
    $this->admin = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    app()->instance('test.broadcasts', new ArrayObject);
    Broadcast::extend('capture', fn () => new class extends Broadcaster
    {
        public function auth($request): mixed
        {
            return null;
        }

        public function validAuthenticationResponse($request, $result): mixed
        {
            return null;
        }

        public function broadcast(array $channels, $event, array $payload = []): void
        {
            broadcasts()->append([array_map(strval(...), $channels), $event, $payload['socket'] ?? null]);
        }
    });
    config(['broadcasting.connections.capture' => ['driver' => 'capture'], 'broadcasting.default' => 'capture']);
    // Browsers connect where the server does, whatever a local .env says.
    config(['larasell.admin.panel.live' => ['host' => null, 'port' => null, 'scheme' => null]]);
});

it('tells the browser to reload the sidebar on the private admin channel when sales channels change', function () {
    // Arrange
    $this->actingAs($this->admin, 'larasell-admin');

    // Act
    $response = $this->get(route('larasell.admin.home'), ['X-Inertia' => 'true']);

    // Assert
    $response->assertJsonPath('liveProps.salesChannels.listeners', [[
        'channel' => ['name' => 'larasell-admin.sales-channels', 'type' => 'private'],
        'events' => ['sales-channels.changed'],
    ]]);
});

it('broadcasts to the other admins when a channel is created, renamed or deleted', function () {
    // Arrange
    $this->actingAs($this->admin, 'larasell-admin');
    $headers = ['X-Socket-ID' => '1234.5678'];

    // Act
    $this->post(route('larasell.admin.sales-channels.store'), [
        'key' => 'b2b', 'name' => 'Wholesale', 'type' => 'storefront', 'locales' => ['en'], 'currencies' => ['USD'],
        'default_locale' => 'en', 'default_currency' => 'USD', 'active' => '1',
    ], $headers)->assertSessionHasNoErrors();
    $b2b = SalesChannel::query()->where('key', 'b2b')->sole();
    $this->put(route('larasell.admin.sales-channels.update', $b2b), [
        'name' => 'Trade', 'locales' => ['en'], 'currencies' => ['USD'], 'default_locale' => 'en', 'default_currency' => 'USD', 'active' => '1',
    ], $headers)->assertSessionHasNoErrors();
    $this->delete(route('larasell.admin.sales-channels.destroy', $b2b), ['confirmation' => 'Trade'], $headers)->assertSessionHasNoErrors();

    // Assert
    $changed = [['private-larasell-admin.sales-channels'], 'sales-channels.changed', '1234.5678'];
    expect(broadcasts()->getArrayCopy())->toBe([$changed, $changed, $changed]);
});

it('broadcasts nothing for other changes to a channel, or for a delete that was refused', function () {
    // Arrange
    $channel = app(CreateSalesChannel::class)->handle(['key' => 'b2b', 'name' => 'B2B', 'type' => 'storefront', 'locales' => ['en', 'de'], 'currencies' => ['USD']], origin());
    Cart::createIn(SalesContext::for('b2b', 'en'));
    broadcasts()->exchangeArray([]);
    $this->actingAs($this->admin, 'larasell-admin');

    // Act
    $this->put(route('larasell.admin.sales-channels.update', $channel), [
        'name' => 'B2B', 'locales' => ['de', 'en'], 'currencies' => ['USD'], 'default_locale' => 'de', 'default_currency' => 'USD', 'active' => '0',
    ])->assertSessionHasNoErrors();
    $this->delete(route('larasell.admin.sales-channels.destroy', $channel), ['confirmation' => 'B2B'])->assertSessionHasErrors('channel');

    // Assert
    expect(broadcasts()->getArrayCopy())->toBe([])
        ->and(SalesChannel::query()->whereKey($channel->id)->exists())->toBeTrue();
});

it('broadcasts a rename made outside the panel too, once the transaction it ran in commits', function () {
    // Act
    DB::transaction(function () {
        app(UpdateSalesChannel::class)->handle(new SalesChannelId(1), ['name' => 'Webshop'], origin());

        expect(broadcasts()->count())->toBe(0);
    });

    // Assert
    expect(broadcasts()->getArrayCopy())->toBe([[['private-larasell-admin.sales-channels'], 'sales-channels.changed', null]]);
});

it('lets only signed-in admins listen on the admin channel', function () {
    // Arrange
    // Before the providers boot, since a channel is allowed on the broadcaster that is the default then.
    $this->bootingWith(fn () => config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb' => [
        'driver' => 'reverb', 'key' => 'key', 'secret' => 'secret', 'app_id' => '1', 'options' => ['host' => 'localhost', 'port' => 8080, 'scheme' => 'http'],
    ]]));
    $this->admin = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $request = ['socket_id' => '1234.5678', 'channel_name' => 'private-larasell-admin.sales-channels'];

    // Act
    $guest = $this->post(route('larasell.admin.broadcasting.auth'), $request);
    $admin = $this->actingAs($this->admin, 'larasell-admin')->post(route('larasell.admin.broadcasting.auth'), $request);

    // Assert
    expect($guest->isSuccessful())->toBeFalse();
    $admin->assertOk()->assertJsonStructure(['auth']);
});

it('gives the browser what it needs to connect to Reverb or Pusher, and nothing for other broadcasters', function () {
    // Arrange
    $this->actingAs($this->admin, 'larasell-admin');
    config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb' => [
        'driver' => 'reverb', 'key' => 'public-key', 'secret' => 'secret', 'app_id' => '1', 'options' => ['host' => 'ws.example', 'port' => 8080, 'scheme' => 'http'],
    ]]);

    // Act
    $reverb = $this->get(route('larasell.admin.home'))->getContent();
    config(['broadcasting.default' => 'log']);
    $log = $this->get(route('larasell.admin.home'))->getContent();

    // Assert
    preg_match('#<script type="application/json" id="larasell-admin-live">(.*?)</script>#s', $reverb, $match);
    expect(json_decode($match[1] ?? 'null', true))->toBe([
        'broadcaster' => 'reverb',
        'key' => 'public-key',
        'wsHost' => 'ws.example',
        'wsPort' => 8080,
        'wssPort' => 8080,
        'forceTLS' => false,
        'authEndpoint' => route('larasell.admin.broadcasting.auth'),
    ])
        ->and($log)->not->toContain('larasell-admin-live');
});

it('lets browsers reach the broadcaster at another address than the server does, e.g. behind a proxy', function () {
    // Arrange
    $this->actingAs($this->admin, 'larasell-admin');
    config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb' => [
        'driver' => 'reverb', 'key' => 'public-key', 'secret' => 'secret', 'app_id' => '1', 'options' => ['host' => 'localhost', 'port' => 8080, 'scheme' => 'http'],
    ], 'larasell.admin.panel.live' => ['host' => 'shop.example', 'port' => 443, 'scheme' => 'https']]);

    // Act
    $page = $this->get(route('larasell.admin.home'))->getContent();

    // Assert
    preg_match('#<script type="application/json" id="larasell-admin-live">(.*?)</script>#s', $page, $match);
    expect(json_decode($match[1] ?? 'null', true))->toMatchArray(['wsHost' => 'shop.example', 'wsPort' => 443, 'wssPort' => 443, 'forceTLS' => true]);
});
