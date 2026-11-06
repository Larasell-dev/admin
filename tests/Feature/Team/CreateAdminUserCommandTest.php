<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Larasell\Admin\Platform\Auth\Models\Admin;

/**
 * Fakes the Have I Been Pwned range API, reporting the given passwords as leaked.
 *
 * @param  list<string>  $leakedPasswords
 */
function fakePwnedPasswords(array $leakedPasswords = []): void
{
    $lines = array_map(
        fn (string $password) => substr(strtoupper(sha1($password)), 5).':42',
        $leakedPasswords,
    );

    Http::fake([
        'api.pwnedpasswords.com/*' => Http::response(implode("\n", [
            '0018A45C4D1DEF81644B54AB7F969B88D65:1',
            ...$lines,
        ])),
    ]);
}

it('creates an admin', function () {
    // Act
    $this->artisan('larasell:create-admin', [
        '--name' => 'New Admin',
        '--email' => 'admin@example.com',
        '--password' => 'Correct-Horse-7-Battery',
    ])
        ->expectsOutputToContain('Admin user [admin@example.com] created.')
        ->assertSuccessful();

    // Assert
    $admin = Admin::query()->sole();

    expect($admin->name)->toBe('New Admin')
        ->and($admin->email)->toBe('admin@example.com')
        ->and(Hash::check('Correct-Horse-7-Battery', $admin->password))->toBeTrue();
});

it('fails with a clear message when an admin with the email already exists', function () {
    // Arrange
    Admin::create([
        'name' => 'Existing Admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    // Act
    $this->artisan('larasell:create-admin', [
        '--name' => 'New Admin',
        '--email' => 'admin@example.com',
        '--password' => 'Correct-Horse-7-Battery',
    ])
        ->expectsOutputToContain('An admin with the email [admin@example.com] already exists.')
        ->assertFailed();

    // Assert
    expect(Admin::query()->sole()->name)->toBe('Existing Admin');
});

it('rejects weak passwords', function (string $password, string $error) {
    // Act
    $this->artisan('larasell:create-admin', [
        '--name' => 'New Admin',
        '--email' => 'admin@example.com',
        '--password' => $password,
    ])
        ->expectsOutputToContain($error)
        ->assertFailed();

    // Assert
    expect(Admin::query()->exists())->toBeFalse();
})->with([
    'too short' => ['Short-7-Pwd', 'The password field must be at least 12 characters.'],
    'no uppercase letter' => ['correct-horse-7-battery', 'The password field must contain at least one uppercase and one lowercase letter.'],
    'no lowercase letter' => ['CORRECT-HORSE-7-BATTERY', 'The password field must contain at least one uppercase and one lowercase letter.'],
    'no number' => ['Correct-Horse-Battery', 'The password field must contain at least one number.'],
    'no symbol' => ['CorrectHorse7Battery', 'The password field must contain at least one symbol.'],
]);

it('rejects compromised passwords in production', function () {
    // Arrange
    $this->app->detectEnvironment(fn () => 'production');
    fakePwnedPasswords(['Correct-Horse-7-Battery']);

    // Act
    $this->artisan('larasell:create-admin', [
        '--name' => 'New Admin',
        '--email' => 'admin@example.com',
        '--password' => 'Correct-Horse-7-Battery',
    ])
        ->expectsOutputToContain('The given password has appeared in a data leak.')
        ->assertFailed();

    // Assert
    expect(Admin::query()->exists())->toBeFalse();
});

it('accepts uncompromised passwords in production', function () {
    // Arrange
    $this->app->detectEnvironment(fn () => 'production');
    fakePwnedPasswords();

    // Act
    $this->artisan('larasell:create-admin', [
        '--name' => 'New Admin',
        '--email' => 'admin@example.com',
        '--password' => 'Correct-Horse-7-Battery',
    ])->assertSuccessful();

    // Assert
    Http::assertSentCount(1);
    expect(Admin::query()->exists())->toBeTrue();
});

it('does not check for compromised passwords outside production', function () {
    // Arrange
    fakePwnedPasswords(['Correct-Horse-7-Battery']);

    // Act
    $this->artisan('larasell:create-admin', [
        '--name' => 'New Admin',
        '--email' => 'admin@example.com',
        '--password' => 'Correct-Horse-7-Battery',
    ])->assertSuccessful();

    // Assert
    Http::assertNothingSent();
    expect(Admin::query()->exists())->toBeTrue();
});
