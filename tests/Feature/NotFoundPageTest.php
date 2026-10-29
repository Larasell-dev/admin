<?php

declare(strict_types=1);

use Illuminate\Contracts\Debug\ExceptionHandler;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Symfony\Component\HttpFoundation\Response;

it('shows the 404 page in the panel for unknown admin addresses and missing records', function (string $uri) {
    $this->actingAs(
        Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']),
        'larasell-admin',
    );

    $response = $this->get($uri)
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('NotFound', shouldExist: false)
            ->where('admin.email', 'ada@example.com')
            ->has('navigation'));

    expect($response->getContent())->toContain('>Page not found - Admin</title>');
})->with([
    'unknown address' => '/admin/does/not/exist',
    'missing admin' => '/admin/settings/admins/999/edit',
]);

it('shows guests the 404 page without the panel', function () {
    $this->get('/admin/does-not-exist') // @phpstan-ignore larasell.preferRouteHelper
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('GuestNotFound', shouldExist: false)
            ->where('admin', null));
});

it("keeps the app's own error response callback for other addresses", function () {
    $this->bootingWith(fn () => app(ExceptionHandler::class)->respondUsing(
        fn (Response $response) => $response->setContent('The app 404 page'),
    ));

    $this->get('/does-not-exist')->assertNotFound()->assertSee('The app 404 page'); // @phpstan-ignore larasell.preferRouteHelper
});

it('keeps plain 404s for other methods, JSON requests and other addresses', function () {
    $this->actingAs(
        Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']),
        'larasell-admin',
    );

    $this->post('/admin/does-not-exist')->assertNotFound()->assertHeaderMissing('X-Inertia'); // @phpstan-ignore larasell.preferRouteHelper
    $this->getJson(route('larasell.admin.settings.admins.edit', 999))->assertNotFound()->assertJsonMissingPath('component');
    expect($this->get('/does-not-exist')->assertNotFound()->getContent())->not->toContain('data-page'); // @phpstan-ignore larasell.preferRouteHelper
});
