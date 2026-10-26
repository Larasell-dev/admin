<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Larasell\Admin\Models\Admin;

dataset('non-local environments', ['production', 'staging', 'testing']);

it('does not expose dev routes outside the local environment', function (string $environment) {
    $this->inEnvironment($environment);

    Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    expect(Route::has('larasell.admin.dev.index'))->toBeFalse()
        ->and(Route::has('larasell.admin.dev.login'))->toBeFalse()
        ->and(Route::has('larasell.admin.dev.logout'))->toBeFalse();

    // The routes aren't registered, so there are no route names to use.
    $this->get('/admin/__dev')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->get('/admin/__dev/login')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->get('/admin/__dev/logout')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper

    expect(auth('larasell-admin')->check())->toBeFalse();
})->with('non-local environments');

it('does not offer the dev login button outside the local environment', function (string $environment) {
    $this->inEnvironment($environment);

    $this->get(route('larasell.admin.login'))->assertInertia(fn (Assert $page) => $page
        ->component('Login')
        ->where('devLoginUrl', null)
    );
})->with('non-local environments');

it('offers the dev login button in the local environment', function () {
    $this->inEnvironment('local');

    $this->get(route('larasell.admin.login'))->assertInertia(fn (Assert $page) => $page
        ->component('Login')
        ->where('devLoginUrl', route('larasell.admin.dev.login'))
    );
});

it('lists the dev routes', function () {
    $this->inEnvironment('local');

    $this->get(route('larasell.admin.dev.index'))
        ->assertOk()
        ->assertJsonCount(3, 'routes')
        ->assertJsonFragment(['uri' => 'admin/__dev'])
        ->assertJsonFragment(['uri' => 'admin/__dev/login'])
        ->assertJsonFragment(['uri' => 'admin/__dev/logout']);
});

it('logs in as the first admin by default', function () {
    $this->inEnvironment('local');

    $first = Admin::create(['name' => 'First', 'email' => 'first@example.com', 'password' => 'password']);
    Admin::create(['name' => 'Second', 'email' => 'second@example.com', 'password' => 'password']);

    $this->get(route('larasell.admin.dev.login'))->assertRedirect(route('larasell.admin.home'));

    expect(auth('larasell-admin')->id())->toBe($first->id);
});

it('logs in as the admin with the given email', function () {
    $this->inEnvironment('local');

    Admin::create(['name' => 'First', 'email' => 'first@example.com', 'password' => 'password']);
    $second = Admin::create(['name' => 'Second', 'email' => 'second@example.com', 'password' => 'password']);

    $this->get(route('larasell.admin.dev.login', ['email' => 'second@example.com', 'redirect' => route('larasell.admin.profile', absolute: false)]))
        ->assertRedirect(route('larasell.admin.profile'));

    expect(auth('larasell-admin')->id())->toBe($second->id);
});

it('creates a dev admin when none exists', function () {
    $this->inEnvironment('local');

    $this->get(route('larasell.admin.dev.login'))->assertRedirect(route('larasell.admin.home'));

    expect(auth('larasell-admin')->user()->email)->toBe('dev@example.com');
});

it('logs out even when already authenticated', function () {
    $this->inEnvironment('local');

    $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.dev.logout'))
        ->assertRedirect(route('larasell.admin.login'));

    expect(auth('larasell-admin')->check())->toBeFalse();
});
