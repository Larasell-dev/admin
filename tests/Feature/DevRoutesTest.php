<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Models\Admin;

dataset('non-local environments', ['production', 'staging', 'testing']);

it('does not expose dev routes outside the local environment', function (string $environment) {
    $this->inEnvironment($environment);

    Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    expect(Route::has('larasell.admin.dev.index'))->toBeFalse()
        ->and(Route::has('larasell.admin.dev.login'))->toBeFalse()
        ->and(Route::has('larasell.admin.dev.logout'))->toBeFalse();

    $this->get('/admin/__dev')->assertNotFound();
    $this->get('/admin/__dev/login')->assertNotFound();
    $this->get('/admin/__dev/logout')->assertNotFound();

    expect(auth('larasell-admin')->check())->toBeFalse();
})->with('non-local environments');

it('lists the dev routes', function () {
    $this->inEnvironment('local');

    $this->get('/admin/__dev')
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

    $this->get('/admin/__dev/login')->assertRedirect('/admin');

    expect(auth('larasell-admin')->id())->toBe($first->id);
});

it('logs in as the admin with the given email', function () {
    $this->inEnvironment('local');

    Admin::create(['name' => 'First', 'email' => 'first@example.com', 'password' => 'password']);
    $second = Admin::create(['name' => 'Second', 'email' => 'second@example.com', 'password' => 'password']);

    $this->get('/admin/__dev/login?email=second@example.com&redirect=/admin/profile')
        ->assertRedirect('/admin/profile');

    expect(auth('larasell-admin')->id())->toBe($second->id);
});

it('creates a dev admin when none exists', function () {
    $this->inEnvironment('local');

    $this->get('/admin/__dev/login')->assertRedirect('/admin');

    expect(auth('larasell-admin')->user()->email)->toBe('dev@example.com');
});

it('logs out even when already authenticated', function () {
    $this->inEnvironment('local');

    $admin = Admin::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/__dev/logout')
        ->assertRedirect('/admin/login');

    expect(auth('larasell-admin')->check())->toBeFalse();
});
