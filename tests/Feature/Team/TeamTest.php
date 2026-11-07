<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;

/**
 * The admins table rows as the signed-in admin sees them.
 *
 * @return array<int, array<string, mixed>>
 */
function adminRows(Admin $viewer): array
{
    return test()->actingAs($viewer, 'larasell-admin')
        ->get(route('larasell.admin.settings.team.index'))
        ->assertOk()
        ->inertiaProps('members.rows');
}

it('lists the admins by name and email in the settings', function () {
    // Arrange
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    // Act & Assert
    $this->actingAs($alan, 'larasell-admin')
        ->get(route('larasell.admin.settings.team.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Team/Pages/Index')
            ->where('createUrl', route('larasell.admin.settings.team.create'))
            ->where('members.title', 'Team')
            ->where('members.columns', [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'text'],
            ])
            ->where('members.rows.0._link', route('larasell.admin.settings.team.edit', $ada))
            ->where('members.rows.0.name', 'Ada Lovelace')
            ->where('members.rows.0.email', 'ada@example.com')
            ->where('members.rows.1.name', 'Alan Turing')
            ->where('members.rows.1.email', 'alan@example.com'));
});

it('offers to delete every admin but the signed-in one', function () {
    // Arrange
    Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    // Act
    [$adaRow, $alanRow] = adminRows($alan);

    // Assert
    expect($adaRow['_actions'][0])
        ->toMatchArray(['label' => 'Delete', 'method' => 'post', 'destructive' => true, 'disabled' => false, 'icon' => 'DeleteBin'])
        ->and($adaRow['_actions'][0]['confirm']['title'])->toBe('Delete member')
        ->and($adaRow['_actions'][0]['confirm']['confirmationText'])->toBe('ada@example.com')
        ->and($alanRow['_actions'][0]['disabled'])->toBeTrue();
});

it('deletes another admin and their avatar', function () {
    // Arrange
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);
    $disk = Storage::fake(config('larasell.admin.panel.disk'));
    $disk->put('avatars/ada.png', 'ada');
    $disk->put('avatars/alan.png', 'alan');
    $ada->update(['avatar_path' => 'avatars/ada.png']);
    $alan->update(['avatar_path' => 'avatars/alan.png']);

    $delete = adminRows($alan)[0]['_actions'][0]['url'];

    // Act
    $this->from(route('larasell.admin.settings.team.index'))
        ->post($delete, ['confirmation' => 'ada@example.com'])
        ->assertRedirect(route('larasell.admin.settings.team.index'));

    // Assert
    expect(Admin::query()->pluck('email')->all())->toBe(['alan@example.com']);
    $disk->assertMissing('avatars/ada.png');
    $disk->assertExists('avatars/alan.png');
});

it('keeps an admin until their email is typed to confirm', function (?string $confirmation) {
    // Arrange
    Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    $delete = adminRows($alan)[0]['_actions'][0]['url'];

    // Act
    $this->post($delete, ['confirmation' => $confirmation])
        ->assertSessionHasErrors(['confirmation' => 'Type "ada@example.com" to confirm.']);

    // Assert
    expect(Admin::query()->count())->toBe(2);
})->with([null, 'Ada Lovelace', 'ADA@example.com']);

it('refuses to delete the signed-in admin', function () {
    // Arrange
    Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    $delete = adminRows($alan)[1]['_actions'][0]['url'];

    // Act
    $this->post($delete, ['confirmation' => 'alan@example.com'])->assertForbidden();

    // Assert
    expect(Admin::query()->count())->toBe(2);
});

it('opens the first settings page from Settings', function () {
    // Arrange
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    // Act & Assert
    $this->actingAs($ada, 'larasell-admin')
        ->get(route('larasell.admin.settings'))
        ->assertRedirect(route('larasell.admin.settings.team.index'));
});

it('opens the new admin page in the settings', function () {
    // Arrange
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    // Act & Assert
    $this->actingAs($ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.team.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Team/Pages/Create')
            ->where('storeUrl', route('larasell.admin.settings.team.store'))
            ->where('indexUrl', route('larasell.admin.settings.team.index')));
});

it('adds an admin and opens their page', function () {
    // Arrange
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    // Act
    $response = $this->actingAs($ada, 'larasell-admin')
        ->post(route('larasell.admin.settings.team.store'), ['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'Correct-Horse-9']);

    // Assert
    $grace = Admin::query()->where('email', 'grace@example.com')->firstOrFail();
    $response->assertRedirect(route('larasell.admin.settings.team.edit', $grace));
    expect($grace->name)->toBe('Grace Hopper')
        ->and(Hash::check('Correct-Horse-9', $grace->password))->toBeTrue();
});

it('rejects a new admin with a taken email or a weak password', function () {
    // Arrange
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    // Act
    $this->actingAs($ada, 'larasell-admin')
        ->from(route('larasell.admin.settings.team.create'))
        ->post(route('larasell.admin.settings.team.store'), ['name' => 'Ada Again', 'email' => 'ada@example.com', 'password' => 'password'])
        ->assertRedirect(route('larasell.admin.settings.team.create'))
        ->assertSessionHasErrors(['email', 'password']);

    // Assert
    expect(Admin::query()->count())->toBe(1);
});

it('opens an admin to edit in the settings', function () {
    // Arrange
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    // Act & Assert
    $this->actingAs($ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.team.edit', $alan))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Team/Pages/Edit')
            ->where('member.name', 'Alan Turing')
            ->where('member.email', 'alan@example.com')
            ->where('admin.email', 'ada@example.com')
            ->where('updateUrl', route('larasell.admin.settings.team.update', $alan))
            ->where('indexUrl', route('larasell.admin.settings.team.index')));
});

it('updates an admin and keeps their password when none is given', function () {
    // Arrange
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'Original-Pass-1']);

    // Act
    $this->actingAs($ada, 'larasell-admin')
        ->put(route('larasell.admin.settings.team.update', $alan), ['name' => 'Alan M. Turing', 'email' => 'turing@example.com', 'password' => ''])
        ->assertRedirect(route('larasell.admin.settings.team.edit', $alan));

    // Assert
    $alan = $alan->fresh();
    expect($alan->name)->toBe('Alan M. Turing')
        ->and($alan->email)->toBe('turing@example.com')
        ->and(Hash::check('Original-Pass-1', $alan->password))->toBeTrue();
});

it('changes an admin\'s password when a new one is given', function () {
    // Arrange
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    // Act
    $this->actingAs($ada, 'larasell-admin')
        ->put(route('larasell.admin.settings.team.update', $alan), ['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'Brand-New-Pass-2'])
        ->assertSessionHasNoErrors();

    // Assert
    expect(Hash::check('Brand-New-Pass-2', $alan->fresh()->password))->toBeTrue();
});

it('rejects an update with another admin\'s email or a weak password', function () {
    // Arrange
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    // Act & Assert
    $this->actingAs($ada, 'larasell-admin')
        ->put(route('larasell.admin.settings.team.update', $alan), ['name' => 'Alan Turing', 'email' => 'ada@example.com', 'password' => 'short'])
        ->assertSessionHasErrors(['email', 'password']);

    $this->put(route('larasell.admin.settings.team.update', $alan), ['name' => 'Alan Turing', 'email' => 'alan@example.com'])
        ->assertSessionHasNoErrors();
});

it('requires a signed-in admin', function (string $uri) {
    // Arrange
    Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    // Act & Assert
    $this->get($uri)->assertRedirect(route('larasell.admin.login'));
})->with(['/admin/settings', '/admin/settings/team', '/admin/settings/team/create', '/admin/settings/team/1/edit']);
