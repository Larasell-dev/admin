<?php

declare(strict_types=1);

use Larasell\Admin\Models\Admin;

it('lists all admins', function () {
    Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    Admin::create(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'password']);

    $this->artisan('larasell:list-admins')
        ->expectsTable(['ID', 'Name', 'Email'], [
            [1, 'Ada Lovelace', 'ada@example.com'],
            [2, 'Grace Hopper', 'grace@example.com'],
        ])
        ->assertSuccessful();
});

it('tells the user when there are no admins', function () {
    $this->artisan('larasell:list-admins')
        ->expectsOutputToContain('No admins found.')
        ->assertSuccessful();
});
