<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Platform\Auth\Models\Admin;

it('registers the admin routes under the configured prefix', function () {
    // Act & Assert
    expect(Route::has('larasell.admin.login'))->toBeTrue()
        ->and(Route::has('larasell.admin.home'))->toBeTrue()
        ->and(Route::has('larasell.admin.logout'))->toBeTrue();
});

it('shows the login page for guests', function () {
    // Act & Assert
    $this->get(route('larasell.admin.login'))->assertOk();
});

it('redirects guests to the admin login', function () {
    // Act & Assert
    $this->get(route('larasell.admin.home'))->assertRedirect(route('larasell.admin.login'));
});

it('redirects authenticated admins away from the login page', function () {
    // Arrange
    $admin = Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    // Act & Assert
    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.login'))
        ->assertRedirect(route('larasell.admin.home'));
});

it('allows authenticated admins to access the admin home', function () {
    // Arrange
    $admin = Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    // Act & Assert
    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.home'))
        ->assertOk();
});
