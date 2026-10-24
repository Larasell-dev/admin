<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;

beforeEach(function () {
    $this->actingAs(
        Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']),
        'larasell-admin',
    );
});

it('shows the 404 page in the panel for unknown admin addresses and missing records', function (string $uri) {
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
    auth('larasell-admin')->logout();

    $this->get('/admin/does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('GuestNotFound', shouldExist: false)
            ->where('admin', null));
});

it('keeps plain 404s for other methods, JSON requests and other addresses', function () {
    $this->post('/admin/does-not-exist')->assertNotFound()->assertHeaderMissing('X-Inertia');
    $this->getJson('/admin/settings/admins/999/edit')->assertNotFound()->assertJsonMissingPath('component');
    expect($this->get('/does-not-exist')->assertNotFound()->getContent())->not->toContain('data-page');
});
