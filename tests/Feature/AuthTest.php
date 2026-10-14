<?php

declare(strict_types=1);

use Larasell\Admin\Models\Admin;

it('logs an admin in with valid credentials', function () {
    Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->post('/admin/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ])->assertRedirect('/admin');

    expect(auth('larasell-admin')->check())->toBeTrue();
});

it('fails to log an admin in with invalid credentials', function () {
    Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->from('/admin/login')
        ->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])
        ->assertRedirect('/admin/login')
        ->assertSessionHasErrors('email');

    expect(auth('larasell-admin')->check())->toBeFalse();
});

it('logs an admin out', function () {
    $admin = Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->actingAs($admin, 'larasell-admin')
        ->post('/admin/logout')
        ->assertRedirect('/admin/login');

    expect(auth('larasell-admin')->check())->toBeFalse();
});
