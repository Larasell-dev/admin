<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Larasell\Admin\Models\Admin;

it('logs an admin in with valid credentials', function () {
    Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->post(route('larasell.admin.login.store'), [
        'email' => 'admin@example.com',
        'password' => 'password',
    ])->assertRedirect(route('larasell.admin.home'));

    expect(auth('larasell-admin')->check())->toBeTrue();
});

it('fails to log an admin in with invalid credentials', function () {
    Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->from(route('larasell.admin.login'))
        ->post(route('larasell.admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])
        ->assertRedirect(route('larasell.admin.login'))
        ->assertSessionHasErrors('email');

    expect(auth('larasell-admin')->check())->toBeFalse();

    $this->get(route('larasell.admin.login'))->assertInertia(fn (Assert $page) => $page
        ->component('Login')
        ->where('errors.email', __('auth.failed'))
    );
});

it('logs an admin out', function () {
    $admin = Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.logout'))
        ->assertRedirect(route('larasell.admin.login'));

    expect(auth('larasell-admin')->check())->toBeFalse();
});
