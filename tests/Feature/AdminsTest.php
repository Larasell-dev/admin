<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;

it('lists the admins by name and email in the settings', function () {
    Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    $this->actingAs($alan, 'larasell-admin')
        ->get('/admin/settings/admins')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Index')
            ->where('admins.title', 'Admins')
            ->where('admins.columns', [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'text'],
            ])
            ->where('admins.rows.0.name', 'Ada Lovelace')
            ->where('admins.rows.0.email', 'ada@example.com')
            ->where('admins.rows.1.name', 'Alan Turing')
            ->where('admins.rows.1.email', 'alan@example.com'));
});

it('opens the first settings page from Settings', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    $this->actingAs($ada, 'larasell-admin')
        ->get('/admin/settings')
        ->assertRedirect(route('larasell.admin.settings.admins.index'));
});

it('requires a signed-in admin', function (string $uri) {
    $this->get($uri)->assertRedirect('/admin/login');
})->with(['/admin/settings', '/admin/settings/admins']);
