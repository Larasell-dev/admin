<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Larasell\Admin\Platform\Auth\Models\Admin;

it('logs an admin in with valid credentials', function () {
    // Arrange
    Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    // Act
    $this->post(route('larasell.admin.login.store'), [
        'email' => 'admin@example.com',
        'password' => 'password',
    ])->assertRedirect(route('larasell.admin.home'));

    // Assert
    expect(auth('larasell-admin')->check())->toBeTrue();
});

it('signs an admin back in without a redirect for the session expired dialog', function () {
    // Arrange
    Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    // Act & Assert
    $this->postJson(route('larasell.admin.login.store'), [
        'email' => 'admin@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    expect(auth('larasell-admin')->check())->toBeFalse();

    $this->postJson(route('larasell.admin.login.store'), [
        'email' => 'admin@example.com',
        'password' => 'password',
    ])->assertNoContent();

    expect(auth('larasell-admin')->check())->toBeTrue();
});

it('fails to log an admin in with invalid credentials', function () {
    // Arrange
    Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    // Act
    $this->from(route('larasell.admin.login'))
        ->post(route('larasell.admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('larasell.admin.login'))
        ->assertSessionHasErrors('email');

    // Assert
    expect(auth('larasell-admin')->check())->toBeFalse();

    $this->get(route('larasell.admin.login'))->assertInertia(fn (Assert $page) => $page
        ->component('Platform/Auth/Pages/Login')
        ->where('errors.email', __('auth.failed'))
    );
});

it('logs an admin out', function () {
    // Arrange
    $admin = Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    // Act
    $this->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.logout'))
        ->assertRedirect(route('larasell.admin.login'));

    // Assert
    expect(auth('larasell-admin')->check())->toBeFalse();
});
