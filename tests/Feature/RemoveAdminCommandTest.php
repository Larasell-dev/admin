<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Exceptions\AdminRemovalFailed;
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

it('refuses to remove the last admin', function () {
    Admin::query()->where('email', 'grace@example.com')->delete();

    $this->artisan('larasell:remove-admin', ['email' => 'ada@example.com', '--force' => true])
        ->expectsOutputToContain('Admin user [ada@example.com] is the last admin and cannot be removed.')
        ->assertFailed();

    expect(Admin::query()->pluck('email')->all())->toBe(['ada@example.com']);
});

it('deletes the avatar of the removed admin', function () {
    $disk = Storage::fake(config('larasell.admin.panel.disk'));
    $disk->put('avatars/ada.png', 'ada');
    $disk->put('avatars/grace.png', 'grace');
    Admin::query()->where('email', 'ada@example.com')->update(['avatar_path' => 'avatars/ada.png']);
    Admin::query()->where('email', 'grace@example.com')->update(['avatar_path' => 'avatars/grace.png']);

    $this->artisan('larasell:remove-admin', ['email' => 'ada@example.com', '--force' => true])
        ->assertSuccessful();

    $disk->assertMissing('avatars/ada.png');
    $disk->assertExists('avatars/grace.png');
});

it('throws a structured error when the admin is not deleted', function () {
    Admin::deleting(fn () => false);

    expect(fn () => $this->artisan('larasell:remove-admin', ['email' => 'ada@example.com', '--force' => true])->run())
        ->toThrow(function (AdminRemovalFailed $exception) {
            expect($exception->errorCode())->toBe('larasell-admin.admin.not-deleted')
                ->and($exception->getMessage())->toBe('Admin user [ada@example.com] was not deleted.');
        });

    expect(Admin::query()->count())->toBe(2);
});
