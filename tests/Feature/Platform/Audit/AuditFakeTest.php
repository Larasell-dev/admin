<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Audit\Actors\Actor;
use Larasell\Admin\Platform\Audit\Actors\SystemActor;
use Larasell\Admin\Platform\Audit\Actors\UnknownActor;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Enums\AuditSource;
use Larasell\Admin\Platform\Audit\Exceptions\AuditFailed;
use Larasell\Admin\Platform\Audit\Facades\Audit;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Team\Actions\CreateAdmin;
use Larasell\Admin\Team\Actions\DeleteAdmin;
use Larasell\Admin\Team\AuditRecords\AdminCreated;
use Larasell\Admin\Team\AuditRecords\AdminDeleted;
use PHPUnit\Framework\AssertionFailedError;

beforeEach(function () {
    $this->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'Correct-Horse-9']);
});

function createGrace(Actor|UnknownActor $actor = new SystemActor('sso-sync')): Admin
{
    return app(CreateAdmin::class)->handle(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'Correct-Horse-9'], $actor);
}

it('records through the facade to the audit log when not faked', function () {
    // Act
    createGrace();

    // Assert
    expect(Audit::getFacadeRoot())->toBe(app(Auditor::class))
        ->and(DB::table('larasell_audit_log')->where('event', 'admin.created')->count())->toBe(1);
});

it('keeps faked records out of the audit log, and asserts them by class', function () {
    // Arrange
    Audit::fake();

    // Act
    createGrace();

    // Assert
    expect(DB::table('larasell_audit_log')->count())->toBe(0);
    Audit::assertRecorded(AdminCreated::class);
    Audit::assertRecordedTimes(AdminCreated::class, 1);
    Audit::assertNotRecorded(AdminDeleted::class);
});

it('passes the record, its actor and its source to the callback', function () {
    // Arrange
    Audit::fake();
    $actor = new SystemActor('sso-sync');

    // Act
    $grace = createGrace($actor);

    // Assert
    Audit::assertRecorded(AdminCreated::class, fn (AdminCreated $record, Actor|UnknownActor $recordedBy, AuditSource $source) => $record->subject()->id === (string) $grace->id
        && $record->properties() === ['email' => 'grace@example.com']
        && $recordedBy === $actor
        && $source === AuditSource::Cli);
});

it('resolves the signed-in admin as the actor, as the real auditor does', function () {
    // Arrange
    Audit::fake();
    $this->actingAs($this->ada, 'larasell-admin');

    // Act
    $this->post(route('larasell.admin.settings.team.store'), ['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'Correct-Horse-9'])->assertRedirect();

    // Assert
    Audit::assertRecorded(AdminCreated::class, fn (AdminCreated $record, Actor|UnknownActor $actor, AuditSource $source) => $actor instanceof Admin
        && $actor->is($this->ada)
        && $source === AuditSource::Web);
});

it('fails the assertions when the records don\'t match', function () {
    // Arrange
    Audit::fake();
    createGrace();

    // Act & Assert
    expect(fn () => Audit::assertRecorded(AdminDeleted::class))->toThrow(AssertionFailedError::class)
        ->and(fn () => Audit::assertRecorded(AdminCreated::class, fn () => false))->toThrow(AssertionFailedError::class)
        ->and(fn () => Audit::assertRecordedTimes(AdminCreated::class, 2))->toThrow(AssertionFailedError::class)
        ->and(fn () => Audit::assertNotRecorded(AdminCreated::class))->toThrow(AssertionFailedError::class)
        ->and(fn () => Audit::assertNothingRecorded())->toThrow(AssertionFailedError::class);
});

it('asserts nothing was recorded', function () {
    // Arrange
    Audit::fake();

    // Act & Assert
    Audit::assertNothingRecorded();
});

it('still refuses records the real auditor refuses', function () {
    // Arrange
    Audit::fake();
    $record = new class extends AuditRecord
    {
        public function event(): string
        {
            return 'book.lent';
        }

        public function category(): AuditCategory
        {
            return AuditCategory::BusinessCritical;
        }

        public function subject(): ?Subject
        {
            return null;
        }

        public function properties(): array
        {
            return ['password' => 'Correct-Horse-9'];
        }
    };

    // Act & Assert
    expect(fn () => Audit::record($record, new SystemActor('library')))->toThrow(AuditFailed::class)
        ->and(fn () => DB::transaction(fn () => Audit::record($record, new SystemActor('library'))))->toThrow(AuditFailed::class);

    Audit::assertNothingRecorded();
});

it('fails recording when told to, so the change rolls back', function () {
    // Arrange
    Audit::fake()->failing();

    // Act & Assert
    expect(fn () => createGrace())->toThrow(AuditFailed::class);

    expect(Admin::query()->where('email', 'grace@example.com')->exists())->toBeFalse();
    Audit::assertNothingRecorded();
});

it('fakes only the given records, and stores the others', function () {
    // Arrange
    Audit::fake([AdminCreated::class]);

    // Act
    app(DeleteAdmin::class)->handle(createGrace(), new SystemActor('sso-sync'));

    // Assert
    Audit::assertRecorded(AdminCreated::class);
    Audit::assertNotRecorded(AdminDeleted::class);
    expect(DB::table('larasell_audit_log')->pluck('event')->all())->toBe(['admin.deleted']);
});
