<?php

declare(strict_types=1);

use Larasell\Admin\Models\Admin;

beforeEach(function () {
    Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    Admin::create(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'password']);
});

it('removes an admin after confirmation', function () {
    $this->artisan('larasell:remove-admin', ['email' => 'ada@example.com'])
        ->expectsConfirmation('Remove the admin [ada@example.com]?', 'yes')
        ->expectsOutputToContain('Admin user [ada@example.com] removed.')
        ->assertSuccessful();

    expect(Admin::query()->pluck('email')->all())->toBe(['grace@example.com']);
});

it('keeps the admin when the removal is not confirmed', function () {
    $this->artisan('larasell:remove-admin', ['email' => 'ada@example.com'])
        ->expectsConfirmation('Remove the admin [ada@example.com]?', 'no')
        ->expectsOutputToContain('Admin user [ada@example.com] was not removed.')
        ->assertFailed();

    expect(Admin::query()->count())->toBe(2);
});

it('removes an admin without asking when forced', function () {
    $this->artisan('larasell:remove-admin', ['email' => 'ada@example.com', '--force' => true])
        ->doesntExpectOutputToContain('Remove the admin')
        ->assertSuccessful();

    expect(Admin::query()->pluck('email')->all())->toBe(['grace@example.com']);
});

it('refuses to remove an admin in a non-interactive session without force', function () {
    $this->artisan('larasell:remove-admin', ['email' => 'ada@example.com', '--no-interaction' => true])
        ->expectsOutputToContain('Use --force to remove an admin in a non-interactive session.')
        ->assertFailed();

    expect(Admin::query()->count())->toBe(2);
});

it('fails when no admin has the email', function () {
    $this->artisan('larasell:remove-admin', ['email' => 'nobody@example.com', '--force' => true])
        ->expectsOutputToContain('No admin with the email [nobody@example.com] exists.')
        ->assertFailed();

    expect(Admin::query()->count())->toBe(2);
});
