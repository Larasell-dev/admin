<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Platform\Auth\AuditRecords\AdminSignedIn;
use Larasell\Admin\Platform\Auth\AuditRecords\AdminSignedOut;
use Larasell\Admin\Platform\Auth\AuditRecords\AdminSignInFailed;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Team\Actions\CreateAdmin;
use Larasell\Admin\Team\Actions\DeleteAdmin;
use Larasell\Admin\Team\AuditRecords\AdminCreated;
use Larasell\Admin\Team\AuditRecords\AdminDeleted;
use Larasell\Admin\Team\AuditRecords\AdminUpdated;
use Larasell\Larasell\Audit\Actors\Actor;
use Larasell\Larasell\Audit\Actors\SystemActor;
use Larasell\Larasell\Audit\Actors\UnknownActor;
use Larasell\Larasell\Audit\Enums\AuditSource;
use Larasell\Larasell\Audit\Facades\Audit;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Audit\Subject;

beforeEach(function () {
    $this->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'Correct-Horse-9']);
    $this->alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'Correct-Horse-9']);
});

function isAdmin(Actor $actor, Admin $admin): bool
{
    return $actor instanceof Admin && $actor->is($admin);
}

it('audits signing in as the admin who did', function () {
    // Arrange
    Audit::fake();

    // Act
    $this->post(route('larasell.admin.login.store'), ['email' => 'ada@example.com', 'password' => 'Correct-Horse-9'])
        ->assertRedirect(route('larasell.admin.home'));

    // Assert
    Audit::assertRecorded(AdminSignedIn::class, fn (AdminSignedIn $record, Origin $origin) => isAdmin($origin->actor, $this->ada)
        && $record->subject() == new Subject('admin', $this->ada->id)
        && $record->properties() === []
        && $origin->source === AuditSource::Web);
});

it('audits a failed sign-in to an admin\'s email against that admin, without the password', function () {
    // Arrange
    Audit::fake();

    // Act
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->post(route('larasell.admin.login.store'), ['email' => 'ada@example.com', 'password' => 'Wrong-Horse-1'])
        ->assertSessionHasErrors('email');

    // Assert
    Audit::assertRecorded(AdminSignInFailed::class, fn (AdminSignInFailed $record, Origin $origin) => $origin->actor instanceof UnknownActor
        && $origin->actor->reason === 'No one was signed in: the sign-in failed.'
        && $record->subject() == new Subject('admin', $this->ada->id)
        && $record->properties() === ['ip' => '203.0.113.7']);
});

it('audits a failed sign-in to an unknown email with the email that was tried', function () {
    // Arrange
    Audit::fake();

    // Act
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->post(route('larasell.admin.login.store'), ['email' => 'mallory@example.com', 'password' => 'Wrong-Horse-1'])
        ->assertSessionHasErrors('email');

    // Assert
    Audit::assertRecorded(AdminSignInFailed::class, fn (AdminSignInFailed $record) => $record->subject() === null
        && $record->properties() === ['email' => 'mallory@example.com', 'ip' => '203.0.113.7']);
});

it('signs no one in when the sign-in can\'t be audited', function () {
    // Arrange
    Audit::fake()->failing();

    // Act
    $this->post(route('larasell.admin.login.store'), ['email' => 'ada@example.com', 'password' => 'Correct-Horse-9'])
        ->assertServerError();

    // Assert
    expect(auth('larasell-admin')->check())->toBeFalse();
});

it('audits signing out as the admin who did', function () {
    // Arrange
    Audit::fake();

    // Act
    $this->actingAs($this->ada, 'larasell-admin')
        ->post(route('larasell.admin.logout'))
        ->assertRedirect(route('larasell.admin.login'));

    // Assert
    Audit::assertRecorded(AdminSignedOut::class, fn (AdminSignedOut $record, Origin $origin) => isAdmin($origin->actor, $this->ada)
        && $record->subject() == new Subject('admin', $this->ada->id));
});

it('audits adding an admin as the admin who did, without their password', function () {
    // Arrange
    Audit::fake();

    // Act
    $this->actingAs($this->ada, 'larasell-admin')
        ->post(route('larasell.admin.settings.team.store'), ['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'Correct-Horse-9'])
        ->assertRedirect();

    // Assert
    $grace = Admin::query()->where('email', 'grace@example.com')->sole();

    Audit::assertRecorded(AdminCreated::class, fn (AdminCreated $record, Origin $origin) => isAdmin($origin->actor, $this->ada)
        && $record->subject() == new Subject('admin', $grace->id)
        && $record->properties() === ['email' => 'grace@example.com']);
});

it('audits deleting an admin, keeping their email once they are gone', function () {
    // Arrange
    Audit::fake();
    Storage::fake(config('larasell.admin.panel.disk'));
    $delete = $this->actingAs($this->alan, 'larasell-admin')
        ->get(route('larasell.admin.settings.team.index'))
        ->inertiaProps('members.rows.0._actions.0.url');

    // Act
    $this->post($delete, ['confirmation' => 'ada@example.com'])->assertRedirect();

    // Assert
    Audit::assertRecorded(AdminDeleted::class, fn (AdminDeleted $record, Origin $origin) => isAdmin($origin->actor, $this->alan)
        && $record->subject() == new Subject('admin', $this->ada->id)
        && $record->properties() === ['email' => 'ada@example.com']);
});

it('audits which of an admin\'s fields changed in the settings, with the email before and after', function () {
    // Arrange
    Audit::fake();

    // Act
    $this->actingAs($this->ada, 'larasell-admin')
        ->put(route('larasell.admin.settings.team.update', $this->alan), ['name' => 'Alan M. Turing', 'email' => 'turing@example.com', 'password' => 'Brand-New-Pass-2'])
        ->assertRedirect();

    // Assert
    Audit::assertRecorded(AdminUpdated::class, fn (AdminUpdated $record, Origin $origin) => isAdmin($origin->actor, $this->ada)
        && $record->subject() == new Subject('admin', $this->alan->id)
        && $record->properties() === [
            'changed' => ['name', 'email', 'password'],
            'email' => ['from' => 'alan@example.com', 'to' => 'turing@example.com'],
        ]);
});

it('audits what was saved, not what was asked for', function () {
    // Arrange
    Audit::fake();
    // As an app's observer might, keep the email from changing.
    Admin::saving(fn (Admin $admin) => $admin->email = $admin->getOriginal('email'));

    // Act
    $this->actingAs($this->ada, 'larasell-admin')
        ->put(route('larasell.admin.settings.team.update', $this->alan), ['name' => 'Alan M. Turing', 'email' => 'turing@example.com'])
        ->assertRedirect();

    // Assert
    expect($this->alan->fresh()->email)->toBe('alan@example.com');
    Audit::assertRecorded(AdminUpdated::class, fn (AdminUpdated $record) => $record->properties() === ['changed' => ['name']]);
});

it('audits an admin changing their own profile', function () {
    // Arrange
    Audit::fake();

    // Act
    $this->actingAs($this->ada, 'larasell-admin')
        ->put(route('larasell.admin.profile.update'), ['name' => 'Ada King', 'email' => 'ada@example.com'])
        ->assertRedirect();

    // Assert
    Audit::assertRecorded(AdminUpdated::class, fn (AdminUpdated $record, Origin $origin) => isAdmin($origin->actor, $this->ada)
        && $record->subject() == new Subject('admin', $this->ada->id)
        && $record->properties() === ['changed' => ['name']]);
});

it('does not audit saving an admin without changes, or changing only an avatar', function () {
    // Arrange
    Audit::fake();
    Storage::fake(config('larasell.admin.panel.disk'));
    $this->ada->update(['avatar_path' => 'avatars/ada.png']);

    // Act
    $this->actingAs($this->ada, 'larasell-admin')
        ->put(route('larasell.admin.settings.team.update', $this->alan), ['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => ''])
        ->assertRedirect();
    $this->put(route('larasell.admin.profile.update'), ['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'remove_avatar' => true])
        ->assertRedirect();

    // Assert
    expect($this->ada->fresh()->avatar_path)->toBeNull();
    Audit::assertNothingRecorded();
});

it('changes no admin when the change can\'t be audited', function () {
    // Arrange
    Audit::fake()->failing();
    $this->actingAs($this->ada, 'larasell-admin');

    // Act
    $this->post(route('larasell.admin.settings.team.store'), ['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'Correct-Horse-9'])->assertServerError();
    $this->put(route('larasell.admin.settings.team.update', $this->alan), ['name' => 'Alan M. Turing', 'email' => 'alan@example.com', 'password' => ''])->assertServerError();
    $this->put(route('larasell.admin.profile.update'), ['name' => 'Ada King', 'email' => 'ada@example.com'])->assertServerError();

    // Assert
    expect(Admin::query()->orderBy('id')->pluck('name')->all())->toBe(['Ada Lovelace', 'Alan Turing']);
});

it('keeps an admin and their avatar when their deletion can\'t be audited', function () {
    // Arrange
    $disk = Storage::fake(config('larasell.admin.panel.disk'));
    $disk->put('avatars/ada.png', 'ada');
    $this->ada->update(['avatar_path' => 'avatars/ada.png']);
    Audit::fake()->failing();

    // Act & Assert
    expect(fn () => app(DeleteAdmin::class)->handle($this->ada, Origin::cli(new SystemActor('offboarding'))))->toThrow(Exception::class);

    expect(Admin::query()->whereKey($this->ada->id)->exists())->toBeTrue();
    $disk->assertExists('avatars/ada.png');
});

it('audits the admin commands as themselves', function () {
    // Arrange
    Audit::fake();

    // Act
    $this->artisan('larasell:create-admin', ['--name' => 'Grace Hopper', '--email' => 'grace@example.com', '--password' => 'Correct-Horse-9'])->assertSuccessful();
    $this->artisan('larasell:remove-admin', ['email' => 'grace@example.com', '--force' => true])->assertSuccessful();

    // Assert
    Audit::assertRecorded(AdminCreated::class, fn (AdminCreated $record, Origin $origin) => $origin->actor == new SystemActor('larasell:create-admin') && $origin->source === AuditSource::Cli);
    Audit::assertRecorded(AdminDeleted::class, fn (AdminDeleted $record, Origin $origin) => $origin->actor == new SystemActor('larasell:remove-admin') && $origin->source === AuditSource::Cli);
});

it('records the actor callers of the actions pass', function () {
    // Arrange
    Audit::fake();
    $actor = new SystemActor('sso-sync');

    // Act
    $grace = app(CreateAdmin::class)->handle(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'Correct-Horse-9'], Origin::cli($actor));
    app(DeleteAdmin::class)->handle($grace, Origin::cli($actor));

    // Assert
    Audit::assertRecorded(AdminCreated::class, fn (AdminCreated $record, Origin $origin) => $origin->actor === $actor);
    Audit::assertRecorded(AdminDeleted::class, fn (AdminDeleted $record, Origin $origin) => $origin->actor === $actor);
});
