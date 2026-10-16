<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Larasell\Admin\Models\Admin;

function profileAdmin(array $attributes = []): Admin
{
    return Admin::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
        ...$attributes,
    ]);
}

it('redirects guests away from the profile page', function () {
    $this->get('/admin/profile')->assertRedirect('/admin/login');
});

it('shows the profile page with the current admin details', function () {
    $this->actingAs(profileAdmin(), 'larasell-admin')
        ->get('/admin/profile')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Profile')
            ->where('admin.name', 'Test Admin')
            ->where('admin.email', 'admin@example.com')
        );
});

it('updates the admin name and email', function () {
    $admin = profileAdmin();

    $this->actingAs($admin, 'larasell-admin')
        ->put('/admin/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])
        ->assertRedirect('/admin/profile')
        ->assertSessionHasNoErrors();

    expect($admin->fresh())
        ->name->toBe('New Name')
        ->email->toBe('new@example.com');
});

it('allows an admin to keep their own email', function () {
    $admin = profileAdmin();

    $this->actingAs($admin, 'larasell-admin')
        ->put('/admin/profile', [
            'name' => 'New Name',
            'email' => 'admin@example.com',
        ])
        ->assertSessionHasNoErrors();

    expect($admin->fresh()->name)->toBe('New Name');
});

it('validates the profile fields', function (array $payload, string $field) {
    profileAdmin(['email' => 'taken@example.com']);
    $admin = profileAdmin();

    $this->actingAs($admin, 'larasell-admin')
        ->from('/admin/profile')
        ->put('/admin/profile', $payload)
        ->assertRedirect('/admin/profile')
        ->assertSessionHasErrors($field);

    expect($admin->fresh())
        ->name->toBe('Test Admin')
        ->email->toBe('admin@example.com');
})->with([
    'missing name' => [['name' => '', 'email' => 'new@example.com'], 'name'],
    'missing email' => [['name' => 'New Name', 'email' => ''], 'email'],
    'invalid email' => [['name' => 'New Name', 'email' => 'not-an-email'], 'email'],
    'email taken by another admin' => [['name' => 'New Name', 'email' => 'taken@example.com'], 'email'],
]);

it('only shows and updates the authenticated admin', function () {
    $other = profileAdmin(['name' => 'Other Admin', 'email' => 'other@example.com']);
    $admin = profileAdmin();

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/profile?id='.$other->id)
        ->assertInertia(fn (Assert $page) => $page
            ->where('admin.name', 'Test Admin')
            ->where('admin.email', 'admin@example.com')
        );

    $this->actingAs($admin, 'larasell-admin')
        ->put('/admin/profile', [
            'id' => $other->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])
        ->assertSessionHasNoErrors();

    expect($other->fresh())
        ->name->toBe('Other Admin')
        ->email->toBe('other@example.com')
        ->and($admin->fresh()->email)->toBe('new@example.com');
});
