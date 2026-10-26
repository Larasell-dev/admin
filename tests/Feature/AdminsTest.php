<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;

/**
 * The admins table rows as the signed-in admin sees them.
 *
 * @return array<int, array<string, mixed>>
 */
function adminRows(Admin $viewer): array
{
    return test()->actingAs($viewer, 'larasell-admin')
        ->get(route('larasell.admin.settings.admins.index'))
        ->assertOk()
        ->inertiaProps('admins.rows');
}

it('lists the admins by name and email in the settings', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    $this->actingAs($alan, 'larasell-admin')
        ->get(route('larasell.admin.settings.admins.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Index')
            ->where('createUrl', route('larasell.admin.settings.admins.create'))
            ->where('admins.title', 'Admins')
            ->where('admins.columns', [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'email', 'label' => 'Email', 'type' => 'text'],
            ])
            ->where('admins.rows.0._link', route('larasell.admin.settings.admins.edit', $ada))
            ->where('admins.rows.0.name', 'Ada Lovelace')
            ->where('admins.rows.0.email', 'ada@example.com')
            ->where('admins.rows.1.name', 'Alan Turing')
            ->where('admins.rows.1.email', 'alan@example.com'));
});

it('offers to delete every admin but the signed-in one', function () {
    Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    [$adaRow, $alanRow] = adminRows($alan);

    expect($adaRow['_actions'][0])
        ->toMatchArray(['label' => 'Delete', 'method' => 'post', 'destructive' => true, 'disabled' => false, 'icon' => 'DeleteBin'])
        ->and($adaRow['_actions'][0]['confirm']['title'])->toBe('Delete admin')
        ->and($adaRow['_actions'][0]['confirm']['confirmationText'])->toBe('ada@example.com')
        ->and($alanRow['_actions'][0]['disabled'])->toBeTrue();
});

it('deletes another admin and their avatar', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);
    $disk = Storage::fake(config('larasell.admin.panel.disk'));
    $disk->put('avatars/ada.png', 'ada');
    $disk->put('avatars/alan.png', 'alan');
    $ada->update(['avatar_path' => 'avatars/ada.png']);
    $alan->update(['avatar_path' => 'avatars/alan.png']);

    $delete = adminRows($alan)[0]['_actions'][0]['url'];

    $this->from(route('larasell.admin.settings.admins.index'))
        ->post($delete, ['confirmation' => 'ada@example.com'])
        ->assertRedirect(route('larasell.admin.settings.admins.index'));

    expect(Admin::query()->pluck('email')->all())->toBe(['alan@example.com']);
    $disk->assertMissing('avatars/ada.png');
    $disk->assertExists('avatars/alan.png');
});

it('keeps an admin until their email is typed to confirm', function (?string $confirmation) {
    Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    $delete = adminRows($alan)[0]['_actions'][0]['url'];

    $this->post($delete, ['confirmation' => $confirmation])
        ->assertSessionHasErrors(['confirmation' => 'Type "ada@example.com" to confirm.']);

    expect(Admin::query()->count())->toBe(2);
})->with([null, 'Ada Lovelace', 'ADA@example.com']);

it('refuses to delete the signed-in admin', function () {
    Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    $delete = adminRows($alan)[1]['_actions'][0]['url'];

    $this->post($delete, ['confirmation' => 'alan@example.com'])->assertForbidden();

    expect(Admin::query()->count())->toBe(2);
});

it('opens the first settings page from Settings', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    $this->actingAs($ada, 'larasell-admin')
        ->get(route('larasell.admin.settings'))
        ->assertRedirect(route('larasell.admin.settings.admins.index'));
});

it('opens the new admin page in the settings', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    $this->actingAs($ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.admins.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Create')
            ->where('storeUrl', route('larasell.admin.settings.admins.store'))
            ->where('indexUrl', route('larasell.admin.settings.admins.index')));
});

it('adds an admin and opens their page', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    $response = $this->actingAs($ada, 'larasell-admin')
        ->post(route('larasell.admin.settings.admins.store'), ['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'Correct-Horse-9']);

    $grace = Admin::query()->where('email', 'grace@example.com')->firstOrFail();
    $response->assertRedirect(route('larasell.admin.settings.admins.edit', $grace));
    expect($grace->name)->toBe('Grace Hopper')
        ->and(Hash::check('Correct-Horse-9', $grace->password))->toBeTrue();
});

it('rejects a new admin with a taken email or a weak password', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    $this->actingAs($ada, 'larasell-admin')
        ->from(route('larasell.admin.settings.admins.create'))
        ->post(route('larasell.admin.settings.admins.store'), ['name' => 'Ada Again', 'email' => 'ada@example.com', 'password' => 'password'])
        ->assertRedirect(route('larasell.admin.settings.admins.create'))
        ->assertSessionHasErrors(['email', 'password']);

    expect(Admin::query()->count())->toBe(1);
});

it('opens an admin to edit in the settings', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    $this->actingAs($ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.admins.edit', $alan))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Edit')
            ->where('editedAdmin.name', 'Alan Turing')
            ->where('editedAdmin.email', 'alan@example.com')
            ->where('admin.email', 'ada@example.com')
            ->where('updateUrl', route('larasell.admin.settings.admins.update', $alan))
            ->where('indexUrl', route('larasell.admin.settings.admins.index')));
});

it('updates an admin and keeps their password when none is given', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'Original-Pass-1']);

    $this->actingAs($ada, 'larasell-admin')
        ->put(route('larasell.admin.settings.admins.update', $alan), ['name' => 'Alan M. Turing', 'email' => 'turing@example.com', 'password' => ''])
        ->assertRedirect(route('larasell.admin.settings.admins.edit', $alan));

    $alan = $alan->fresh();
    expect($alan->name)->toBe('Alan M. Turing')
        ->and($alan->email)->toBe('turing@example.com')
        ->and(Hash::check('Original-Pass-1', $alan->password))->toBeTrue();
});

it('changes an admin\'s password when a new one is given', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    $this->actingAs($ada, 'larasell-admin')
        ->put(route('larasell.admin.settings.admins.update', $alan), ['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'Brand-New-Pass-2'])
        ->assertSessionHasNoErrors();

    expect(Hash::check('Brand-New-Pass-2', $alan->fresh()->password))->toBeTrue();
});

it('rejects an update with another admin\'s email or a weak password', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    $this->actingAs($ada, 'larasell-admin')
        ->put(route('larasell.admin.settings.admins.update', $alan), ['name' => 'Alan Turing', 'email' => 'ada@example.com', 'password' => 'short'])
        ->assertSessionHasErrors(['email', 'password']);

    $this->put(route('larasell.admin.settings.admins.update', $alan), ['name' => 'Alan Turing', 'email' => 'alan@example.com'])
        ->assertSessionHasNoErrors();
});

it('requires a signed-in admin', function (string $uri) {
    Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    $this->get($uri)->assertRedirect(route('larasell.admin.login'));
})->with(['/admin/settings', '/admin/settings/admins', '/admin/settings/admins/create', '/admin/settings/admins/1/edit']);
