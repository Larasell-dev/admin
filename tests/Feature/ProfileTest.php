<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('shares the signed-in admin, profile link and panel url with every admin page', function () {
    $this->actingAs(profileAdmin(), 'larasell-admin')
        ->get('/admin')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('admin.name', 'Test Admin')
            ->where('admin.email', 'admin@example.com')
            ->where('profileUrl', route('larasell.admin.profile'))
            ->where('panelUrl', route('larasell.admin.home'))
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

it('uploads an avatar without assigning it', function () {
    Storage::fake('avatars');
    config(['larasell-admin.disk' => 'avatars']);
    $admin = profileAdmin();

    $response = $this->actingAs($admin, 'larasell-admin')
        ->postJson('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->assertCreated();

    $path = $response->json('path');

    Storage::disk('avatars')->assertExists($path);
    $response->assertJson(['url' => Storage::disk('avatars')->url($path)]);
    expect($admin->fresh()->avatar_path)->toBeNull();
});

it('uses the public disk by default', function () {
    Storage::fake('public');

    $path = $this->actingAs(profileAdmin(), 'larasell-admin')
        ->postJson('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->json('path');

    expect($path)->toBeString();
    Storage::disk('public')->assertExists($path);
});

it('assigns an uploaded avatar when the profile is saved', function () {
    Storage::fake('public');
    $admin = profileAdmin();

    $path = $this->actingAs($admin, 'larasell-admin')
        ->postJson('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->json('path');

    $this->actingAs($admin, 'larasell-admin')
        ->put('/admin/profile', ['name' => 'Test Admin', 'email' => 'admin@example.com', 'avatar' => $path])
        ->assertRedirect('/admin/profile')
        ->assertSessionHasNoErrors();

    expect($admin->fresh()->avatar_path)->toBe($path);

    $this->actingAs($admin->fresh(), 'larasell-admin')
        ->get('/admin/profile')
        ->assertInertia(fn (Assert $page) => $page
            ->where('admin.avatarUrl', Storage::disk('public')->url($path))
        );
});

it('replaces the previous avatar and deletes the old file', function () {
    Storage::fake('public');
    $admin = profileAdmin();
    $old = 'avatars/old.png';
    Storage::disk('public')->put($old, 'old');
    $admin->update(['avatar_path' => $old]);

    $new = $this->actingAs($admin, 'larasell-admin')
        ->postJson('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->json('path');

    Storage::disk('public')->assertExists($old);

    $this->actingAs($admin, 'larasell-admin')
        ->put('/admin/profile', ['name' => 'Test Admin', 'email' => 'admin@example.com', 'avatar' => $new])
        ->assertSessionHasNoErrors();

    expect($admin->fresh()->avatar_path)->toBe($new);
    Storage::disk('public')->assertMissing($old);
    Storage::disk('public')->assertExists($new);
});

it('deletes an unsaved upload when another one is uploaded', function () {
    Storage::fake('public');
    $admin = profileAdmin();

    $first = $this->actingAs($admin, 'larasell-admin')
        ->postJson('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->json('path');
    $second = $this->actingAs($admin, 'larasell-admin')
        ->postJson('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->json('path');

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($second);

    $this->actingAs($admin, 'larasell-admin')
        ->from('/admin/profile')
        ->put('/admin/profile', ['name' => 'Test Admin', 'email' => 'admin@example.com', 'avatar' => $first])
        ->assertSessionHasErrors('avatar');
});

it('only assigns avatars uploaded in the same session', function () {
    Storage::fake('public');
    $other = profileAdmin(['email' => 'other@example.com', 'avatar_path' => 'avatars/other.png']);
    Storage::disk('public')->put('avatars/other.png', 'other');
    $admin = profileAdmin();

    $this->actingAs($admin, 'larasell-admin')
        ->from('/admin/profile')
        ->put('/admin/profile', ['name' => 'New Name', 'email' => 'admin@example.com', 'avatar' => 'avatars/other.png'])
        ->assertSessionHasErrors('avatar');

    expect($admin->fresh())
        ->avatar_path->toBeNull()
        ->name->toBe('Test Admin');
    Storage::disk('public')->assertExists('avatars/other.png');
});

it('keeps the avatar when the profile is saved without a new one', function () {
    Storage::fake('public');
    $admin = profileAdmin(['avatar_path' => 'avatars/current.png']);
    Storage::disk('public')->put('avatars/current.png', 'current');

    $this->actingAs($admin, 'larasell-admin')
        ->put('/admin/profile', ['name' => 'New Name', 'email' => 'admin@example.com'])
        ->assertSessionHasNoErrors();

    expect($admin->fresh()->avatar_path)->toBe('avatars/current.png');
    Storage::disk('public')->assertExists('avatars/current.png');
});

it('removes the avatar', function () {
    Storage::fake('public');
    $admin = profileAdmin(['avatar_path' => 'avatars/current.png']);
    Storage::disk('public')->put('avatars/current.png', 'current');

    $this->actingAs($admin, 'larasell-admin')
        ->put('/admin/profile', ['name' => 'Test Admin', 'email' => 'admin@example.com', 'remove_avatar' => '1'])
        ->assertSessionHasNoErrors();

    expect($admin->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing('avatars/current.png');
});

it('redirects guests away from the avatar upload', function () {
    $this->post('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png', 256, 256)])
        ->assertRedirect('/admin/login');
});

it('rejects invalid avatars', function (Closure $file) {
    Storage::fake('public');

    $this->actingAs(profileAdmin(), 'larasell-admin')
        ->postJson('/admin/profile/avatar', ['avatar' => $file()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('avatar');

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

it('accepts avatars at the dimension limits', function (int $size) {
    Storage::fake('public');

    $this->actingAs(profileAdmin(), 'larasell-admin')
        ->postJson('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png', $size, $size)])
        ->assertCreated();
})->with([128, 2048]);

it('rate limits avatar uploads', function () {
    Storage::fake('public');
    $admin = profileAdmin();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($admin, 'larasell-admin')
            ->postJson('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png', 128, 128)])
            ->assertCreated();
    }

    $this->actingAs($admin, 'larasell-admin')
        ->postJson('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('avatar.png', 128, 128)])
        ->assertTooManyRequests();
});
