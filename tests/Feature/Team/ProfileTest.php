<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Team\Exceptions\InvalidAvatarPath;
use Larasell\Admin\Team\Http\Controllers\AvatarController;

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
    // Act & Assert
    $this->get(route('larasell.admin.profile'))->assertRedirect(route('larasell.admin.login'));
});

it('shows the profile page with the current admin details', function () {
    // Act & Assert
    $this->actingAs(profileAdmin(), 'larasell-admin')
        ->get(route('larasell.admin.profile'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Team/Pages/Profile')
            ->where('admin.name', 'Test Admin')
            ->where('admin.email', 'admin@example.com')
        );
});

it('shares the signed-in admin and profile link with every admin page', function () {
    // Act & Assert
    $this->actingAs(profileAdmin(), 'larasell-admin')
        ->get(route('larasell.admin.home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('admin.name', 'Test Admin')
            ->where('admin.email', 'admin@example.com')
            ->where('profileUrl', route('larasell.admin.profile'))
        );
});

it('updates the admin name and email', function () {
    // Arrange
    $admin = profileAdmin();

    // Act
    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.profile.update'), [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])
        ->assertRedirect(route('larasell.admin.profile'))
        ->assertSessionHasNoErrors();

    // Assert
    expect($admin->fresh())
        ->name->toBe('New Name')
        ->email->toBe('new@example.com');
});

it('allows an admin to keep their own email', function () {
    // Arrange
    $admin = profileAdmin();

    // Act
    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.profile.update'), [
            'name' => 'New Name',
            'email' => 'admin@example.com',
        ])
        ->assertSessionHasNoErrors();

    // Assert
    expect($admin->fresh()->name)->toBe('New Name');
});

it('validates the profile fields', function (array $payload, string $field) {
    // Arrange
    profileAdmin(['email' => 'taken@example.com']);
    $admin = profileAdmin();

    // Act
    $this->actingAs($admin, 'larasell-admin')
        ->from(route('larasell.admin.profile'))
        ->put(route('larasell.admin.profile.update'), $payload)
        ->assertRedirect(route('larasell.admin.profile'))
        ->assertSessionHasErrors($field);

    // Assert
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
    // Arrange
    $other = profileAdmin(['name' => 'Other Admin', 'email' => 'other@example.com']);
    $admin = profileAdmin();

    // Act & Assert
    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.profile', ['id' => $other->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('admin.name', 'Test Admin')
            ->where('admin.email', 'admin@example.com')
        );

    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.profile.update'), [
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

it('uploads an avatar without assigning it', function () {
    // Arrange
    Storage::fake('avatars');
    config(['larasell.admin.panel.disk' => 'avatars']);
    $admin = profileAdmin();

    // Act
    $response = $this->actingAs($admin, 'larasell-admin')
        ->postJson(route('larasell.admin.profile.avatar.store'), ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->assertCreated();

    // Assert
    $path = $response->json('path');

    Storage::disk('avatars')->assertExists($path);
    $response->assertJson(['url' => Storage::disk('avatars')->url($path)]);
    expect($admin->fresh()->avatar_path)->toBeNull();
});

it('uses the public disk by default', function () {
    // Arrange
    Storage::fake('public');

    // Act
    $path = $this->actingAs(profileAdmin(), 'larasell-admin')
        ->postJson(route('larasell.admin.profile.avatar.store'), ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->json('path');

    // Assert
    expect($path)->toBeString();
    Storage::disk('public')->assertExists($path);
});

it('assigns an uploaded avatar when the profile is saved', function () {
    // Arrange
    Storage::fake('public');
    $admin = profileAdmin();

    // Act
    $path = $this->actingAs($admin, 'larasell-admin')
        ->postJson(route('larasell.admin.profile.avatar.store'), ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->json('path');

    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.profile.update'), ['name' => 'Test Admin', 'email' => 'admin@example.com', 'avatar' => $path])
        ->assertRedirect(route('larasell.admin.profile'))
        ->assertSessionHasNoErrors();

    // Assert
    expect($admin->fresh()->avatar_path)->toBe($path);

    $this->actingAs($admin->fresh(), 'larasell-admin')
        ->get(route('larasell.admin.profile'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('admin.avatarUrl', Storage::disk('public')->url($path))
        );
});

it('replaces the previous avatar and deletes the old file', function () {
    // Arrange
    Storage::fake('public');
    $admin = profileAdmin();
    $old = 'avatars/old.png';
    Storage::disk('public')->put($old, 'old');
    $admin->update(['avatar_path' => $old]);

    // Act
    $new = $this->actingAs($admin, 'larasell-admin')
        ->postJson(route('larasell.admin.profile.avatar.store'), ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->json('path');

    // Assert
    Storage::disk('public')->assertExists($old);

    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.profile.update'), ['name' => 'Test Admin', 'email' => 'admin@example.com', 'avatar' => $new])
        ->assertSessionHasNoErrors();

    expect($admin->fresh()->avatar_path)->toBe($new);
    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($new);
});

it('deletes an unsaved upload when another one is uploaded', function () {
    // Arrange
    Storage::fake('public');
    $admin = profileAdmin();

    // Act
    $first = $this->actingAs($admin, 'larasell-admin')
        ->postJson(route('larasell.admin.profile.avatar.store'), ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->json('path');
    $second = $this->actingAs($admin, 'larasell-admin')
        ->postJson(route('larasell.admin.profile.avatar.store'), ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->json('path');

    // Assert
    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $this->actingAs($admin, 'larasell-admin')
        ->from(route('larasell.admin.profile'))
        ->put(route('larasell.admin.profile.update'), ['name' => 'Test Admin', 'email' => 'admin@example.com', 'avatar' => $first])
        ->assertSessionHasErrors('avatar');
});

it('only assigns avatars uploaded in the same session', function () {
    // Arrange
    Storage::fake('public');
    $other = profileAdmin(['email' => 'other@example.com', 'avatar_path' => 'avatars/other.png']);
    Storage::disk('public')->put('avatars/other.png', 'other');
    $admin = profileAdmin();

    // Act
    $this->actingAs($admin, 'larasell-admin')
        ->from(route('larasell.admin.profile'))
        ->put(route('larasell.admin.profile.update'), ['name' => 'New Name', 'email' => 'admin@example.com', 'avatar' => 'avatars/other.png'])
        ->assertSessionHasErrors('avatar');

    // Assert
    expect($admin->fresh())
        ->avatar_path->toBeNull()
        ->name->toBe('Test Admin');
    Storage::disk('public')->assertExists('avatars/other.png');
});

it('keeps the avatar when the profile is saved without a new one', function () {
    // Arrange
    Storage::fake('public');
    $admin = profileAdmin(['avatar_path' => 'avatars/current.png']);
    Storage::disk('public')->put('avatars/current.png', 'current');

    // Act
    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.profile.update'), ['name' => 'New Name', 'email' => 'admin@example.com'])
        ->assertSessionHasNoErrors();

    // Assert
    expect($admin->fresh()->avatar_path)->toBe('avatars/current.png');
    Storage::disk('public')->assertExists('avatars/current.png');
});

it('removes the avatar', function () {
    // Arrange
    Storage::fake('public');
    $admin = profileAdmin(['avatar_path' => 'avatars/current.png']);
    Storage::disk('public')->put('avatars/current.png', 'current');

    // Act
    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.profile.update'), ['name' => 'Test Admin', 'email' => 'admin@example.com', 'remove_avatar' => '1'])
        ->assertSessionHasNoErrors();

    // Assert
    expect($admin->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/current.png');
});

it('redirects guests away from the avatar upload', function () {
    // Act & Assert
    $this->post(route('larasell.admin.profile.avatar.store'), ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->assertRedirect(route('larasell.admin.login'));
});

it('rejects invalid avatars', function (Closure $file) {
    // Arrange
    Storage::fake('public');

    // Act
    $this->actingAs(profileAdmin(), 'larasell-admin')
        ->postJson(route('larasell.admin.profile.avatar.store'), ['avatar' => $file()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('avatar');

    // Assert
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
})->with([
    'missing' => [fn () => null],
    'not an image' => [fn () => UploadedFile::fake()->create('avatar.pdf', 10, 'application/pdf')],
    'svg' => [fn () => UploadedFile::fake()->createWithContent('avatar.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')],
    'gif' => [fn () => UploadedFile::fake()->image('avatar.gif', 256, 256)],
    'not square' => [fn () => UploadedFile::fake()->image('avatar.png', 256, 255)],
    'too small' => [fn () => UploadedFile::fake()->image('avatar.png', 127, 127)],
    'too large dimensions' => [fn () => UploadedFile::fake()->image('avatar.png', 2049, 2049)],
    'file too big' => [fn () => UploadedFile::fake()->image('avatar.png', 256, 256)->size(2049)],
]);

// PHP drops files over its upload_max_filesize before the size rule runs.
it('explains avatars that PHP could not receive', function () {
    // Arrange
    $file = new UploadedFile(UploadedFile::fake()->image('avatar.png', 256, 256)->getPathname(), 'avatar.png', 'image/png', UPLOAD_ERR_INI_SIZE, true);

    // Act & Assert
    $this->actingAs(profileAdmin(), 'larasell-admin')
        ->postJson(route('larasell.admin.profile.avatar.store'), ['avatar' => $file])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['avatar' => 'The picture may be larger than the server accepts.']);
});

it('accepts avatars at the dimension limits', function (int $size) {
    // Arrange
    Storage::fake('public');

    // Act & Assert
    $this->actingAs(profileAdmin(), 'larasell-admin')
        ->postJson(route('larasell.admin.profile.avatar.store'), ['avatar' => UploadedFile::fake()->image('avatar.png', $size, $size)])
        ->assertCreated();
})->with([128, 2048]);

it('rate limits avatar uploads', function () {
    // Arrange
    Storage::fake('public');
    $admin = profileAdmin();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($admin, 'larasell-admin')
            ->postJson(route('larasell.admin.profile.avatar.store'), ['avatar' => UploadedFile::fake()->image('avatar.png', 128, 128)])
            ->assertCreated();
    }

    // Act & Assert
    $this->actingAs($admin, 'larasell-admin')
        ->postJson(route('larasell.admin.profile.avatar.store'), ['avatar' => UploadedFile::fake()->image('avatar.png', 128, 128)])
        ->assertTooManyRequests();
});

it('keeps a file outside avatars/ that the previous avatar path points to', function () {
    // Arrange
    Storage::fake('public');
    Storage::disk('public')->put('invoices/2026.pdf', 'invoice');
    $admin = profileAdmin(['avatar_path' => 'invoices/2026.pdf']);

    // Act
    $update = fn () => $this->withoutExceptionHandling()->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.profile.update'), ['name' => 'Test Admin', 'email' => 'admin@example.com', 'remove_avatar' => true]);

    // Assert
    expect($update)->toThrow(InvalidAvatarPath::class);
    Storage::disk('public')->assertExists('invoices/2026.pdf');
});
