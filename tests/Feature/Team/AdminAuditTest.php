<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Platform\Audit\Actors\SystemActor;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Team\Actions\CreateAdmin;
use Larasell\Admin\Team\Actions\DeleteAdmin;

beforeEach(function () {
    $this->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'Correct-Horse-9']);
    $this->alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'Correct-Horse-9']);
});

/**
 * The one audit record with this event.
 */
function auditRecord(string $event): object
{
    return DB::table('larasell_audit_log')->where('event', $event)->sole();
}

/**
 * @return array<string, mixed>
 */
function propertiesOf(object $record): array
{
    return json_decode($record->properties, true);
}

it('audits signing in as the admin who did', function () {
    // Act
    $this->post(route('larasell.admin.login.store'), ['email' => 'ada@example.com', 'password' => 'Correct-Horse-9'])
        ->assertRedirect(route('larasell.admin.home'));

    // Assert
    $record = auditRecord('admin.signed_in');

    expect($record)->toMatchArray([
        'category' => 'security',
        'actor_type' => 'admin',
        'actor_id' => (string) $this->ada->id,
        'subject_type' => 'admin',
        'subject_id' => (string) $this->ada->id,
        'source' => 'web',
    ])
        ->and(propertiesOf($record))->toBe([]);
});

it('audits a failed sign-in to an admin\'s email against that admin, without the password', function () {
    // Act
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->post(route('larasell.admin.login.store'), ['email' => 'ada@example.com', 'password' => 'Wrong-Horse-1'])
        ->assertSessionHasErrors('email');

    // Assert
    $record = auditRecord('admin.sign_in_failed');

    expect($record)->toMatchArray([
        'category' => 'security',
        'actor_type' => 'unknown',
        'unknown_actor_reason' => 'No one was signed in: the sign-in failed.',
        'subject_type' => 'admin',
        'subject_id' => (string) $this->ada->id,
    ])
        ->and(propertiesOf($record))->toBe(['ip' => '203.0.113.7'])
        ->and($record->properties)->not->toContain('Wrong-Horse-1');
});

it('audits a failed sign-in to an unknown email with the email that was tried', function () {
    // Act
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->post(route('larasell.admin.login.store'), ['email' => 'mallory@example.com', 'password' => 'Wrong-Horse-1'])
        ->assertSessionHasErrors('email');

    // Assert
    $record = auditRecord('admin.sign_in_failed');

    expect($record->subject_type)->toBeNull()
        ->and(propertiesOf($record))->toBe(['email' => 'mallory@example.com', 'ip' => '203.0.113.7']);
});

it('signs no one in when the sign-in can\'t be audited', function () {
    // Arrange
    Schema::drop('larasell_audit_log');

    // Act
    $this->post(route('larasell.admin.login.store'), ['email' => 'ada@example.com', 'password' => 'Correct-Horse-9'])
        ->assertServerError();

    // Assert
    expect(auth('larasell-admin')->check())->toBeFalse();
});

it('audits signing out as the admin who did', function () {
    // Act
    $this->actingAs($this->ada, 'larasell-admin')
        ->post(route('larasell.admin.logout'))
        ->assertRedirect(route('larasell.admin.login'));

    // Assert
    expect(auditRecord('admin.signed_out'))->toMatchArray([
        'category' => 'security',
        'actor_type' => 'admin',
        'actor_id' => (string) $this->ada->id,
        'subject_type' => 'admin',
        'subject_id' => (string) $this->ada->id,
    ]);
});

it('audits adding an admin as the admin who did, without their password', function () {
    // Act
    $this->actingAs($this->ada, 'larasell-admin')
        ->post(route('larasell.admin.settings.team.store'), ['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'Correct-Horse-9'])
        ->assertRedirect();

    // Assert
    $grace = Admin::query()->where('email', 'grace@example.com')->sole();
    $record = auditRecord('admin.created');

    expect($record)->toMatchArray([
        'category' => 'security',
        'actor_type' => 'admin',
        'actor_id' => (string) $this->ada->id,
        'subject_type' => 'admin',
        'subject_id' => (string) $grace->id,
    ])
        ->and(propertiesOf($record))->toBe(['email' => 'grace@example.com'])
        ->and($record->properties)->not->toContain('Correct-Horse-9');
});

it('audits deleting an admin, keeping their email once they are gone', function () {
    // Arrange
    Storage::fake(config('larasell.admin.panel.disk'));
    $delete = $this->actingAs($this->alan, 'larasell-admin')
        ->get(route('larasell.admin.settings.team.index'))
        ->inertiaProps('members.rows.0._actions.0.url');

    // Act
    $this->post($delete, ['confirmation' => 'ada@example.com'])->assertRedirect();

    // Assert
    $record = auditRecord('admin.deleted');

    expect($record)->toMatchArray([
        'actor_type' => 'admin',
        'actor_id' => (string) $this->alan->id,
        'subject_type' => 'admin',
        'subject_id' => (string) $this->ada->id,
    ])
        ->and(propertiesOf($record))->toBe(['email' => 'ada@example.com']);
});

it('audits which of an admin\'s fields changed in the settings, with the email before and after', function () {
    // Act
    $this->actingAs($this->ada, 'larasell-admin')
        ->put(route('larasell.admin.settings.team.update', $this->alan), ['name' => 'Alan M. Turing', 'email' => 'turing@example.com', 'password' => 'Brand-New-Pass-2'])
        ->assertRedirect();

    // Assert
    $record = auditRecord('admin.updated');

    expect($record)->toMatchArray([
        'actor_type' => 'admin',
        'actor_id' => (string) $this->ada->id,
        'subject_type' => 'admin',
        'subject_id' => (string) $this->alan->id,
    ])
        ->and(propertiesOf($record))->toBe([
            'changed' => ['name', 'email', 'password'],
            'email' => ['from' => 'alan@example.com', 'to' => 'turing@example.com'],
        ])
        ->and($record->properties)->not->toContain('Brand-New-Pass-2');
});

it('audits an admin changing their own profile', function () {
    // Act
    $this->actingAs($this->ada, 'larasell-admin')
        ->put(route('larasell.admin.profile.update'), ['name' => 'Ada King', 'email' => 'ada@example.com'])
        ->assertRedirect();

    // Assert
    $record = auditRecord('admin.updated');

    expect($record)->toMatchArray([
        'actor_id' => (string) $this->ada->id,
        'subject_id' => (string) $this->ada->id,
    ])
        ->and(propertiesOf($record))->toBe(['changed' => ['name']]);
});

it('does not audit saving an admin without changes, or changing only an avatar', function () {
    // Arrange
    Storage::fake(config('larasell.admin.panel.disk'));
    $this->ada->update(['avatar_path' => 'avatars/ada.png']);

    // Act
    $this->actingAs($this->ada, 'larasell-admin')
        ->put(route('larasell.admin.settings.team.update', $this->alan), ['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => ''])
        ->assertRedirect();
    $this->put(route('larasell.admin.profile.update'), ['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'remove_avatar' => true])
        ->assertRedirect();

    // Assert
    expect($this->ada->fresh()->avatar_path)->toBeNull()
        ->and(DB::table('larasell_audit_log')->count())->toBe(0);
});

it('changes no admin when the change can\'t be audited', function () {
    // Arrange
    Schema::drop('larasell_audit_log');
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
    Schema::drop('larasell_audit_log');

    // Act & Assert
    expect(fn () => app(DeleteAdmin::class)->handle($this->ada, new SystemActor('offboarding')))->toThrow(Exception::class);

    expect(Admin::query()->whereKey($this->ada->id)->exists())->toBeTrue();
    $disk->assertExists('avatars/ada.png');
});

it('audits the admin commands as themselves', function () {
    // Act
    $this->artisan('larasell:create-admin', ['--name' => 'Grace Hopper', '--email' => 'grace@example.com', '--password' => 'Correct-Horse-9'])->assertSuccessful();
    $this->artisan('larasell:remove-admin', ['email' => 'grace@example.com', '--force' => true])->assertSuccessful();

    // Assert
    expect(auditRecord('admin.created'))->toMatchArray(['actor_type' => 'system', 'actor_id' => 'larasell:create-admin', 'source' => 'cli'])
        ->and(auditRecord('admin.deleted'))->toMatchArray(['actor_type' => 'system', 'actor_id' => 'larasell:remove-admin', 'source' => 'cli']);
});

it('records the actor callers of the actions pass', function () {
    // Act
    $grace = app(CreateAdmin::class)->handle(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'Correct-Horse-9'], new SystemActor('sso-sync'));
    app(DeleteAdmin::class)->handle($grace, new SystemActor('sso-sync'));

    // Assert
    expect(DB::table('larasell_audit_log')->orderBy('id')->pluck('actor_id')->all())->toBe(['sso-sync', 'sso-sync']);
});
