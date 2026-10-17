<?php

declare(strict_types=1);

use Larasell\Admin\Models\Admin;

it('fails with a clear message when an admin with the email already exists', function () {
    Admin::create([
        'name' => 'Existing Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $this->artisan('larasell:create-admin', [
        '--name' => 'New Admin',
        '--email' => 'admin@example.com',
        '--password' => 'secret',
    ])
        ->expectsOutputToContain('An admin with the email [admin@example.com] already exists.')
        ->assertFailed();

    expect(Admin::query()->sole()->name)->toBe('Existing Admin');
});
