<?php

declare(strict_types=1);

use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Media\Events\MediaLibraryChanged;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Media\Actions\CreateMediaFolder;
use Larasell\Larasell\Media\Actions\CreateMediaFolderPayload;
use Larasell\Larasell\Media\Actions\UploadMedia;
use Larasell\Larasell\Media\Actions\UploadMediaPayload;
use Larasell\Larasell\Media\Events\MediaFolderRenamed;
use Larasell\Larasell\Media\MediaFolderId;
use Larasell\Larasell\Media\MediaFolderName;
use Larasell\Larasell\Media\Models\Media;
use Larasell\Larasell\Media\Models\MediaFolder;

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('private-media');
    config(['larasell.media.disks' => ['public' => 'media', 'private' => 'private-media']]);
    test()->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

function adminFolder(string $name, ?MediaFolder $parent = null): MediaFolder
{
    return app(CreateMediaFolder::class)->handle(new CreateMediaFolderPayload(new MediaFolderName($name), $parent === null ? null : MediaFolderId::of($parent)));
}

function adminMediaIn(?MediaFolder $folder): Media
{
    return app(UploadMedia::class)->handle(new UploadMediaPayload(UploadedFile::fake()->image('boots.png'), folderId: $folder === null ? null : MediaFolderId::of($folder)));
}

it('shows a folder\'s subfolders and only its own media, with the whole tree for the breadcrumb', function () {
    // Arrange
    $campaigns = adminFolder('Campaigns');
    $summer = adminFolder('Summer', $campaigns);
    adminFolder('Beach', $summer);
    $inCampaigns = adminMediaIn($campaigns);
    adminMediaIn(null);

    // Act & Assert
    $this->get(route('larasell.admin.media.index', ['folder' => $campaigns->id]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('folderId', $campaigns->id)
            ->where('entries.data.0.key', "folder-{$summer->id}")
            ->where('entries.data.0.media', null)
            ->where('entries.data.0.folder.name', 'Summer')
            ->where('entries.data.0.folder.folderCount', 1)
            ->where('entries.data.0.folder.mediaCount', 0)
            ->where('entries.data.1.folder', null)
            ->where('entries.data.1.media.id', $inCampaigns->id)
            ->has('entries.data', 2)
            ->has('tree', 3)
            ->where('storeUrl', route('larasell.admin.media.store', ['folder' => $campaigns->id])));
});

it('shows the top level for a folder that no longer exists', function () {
    // Arrange
    adminFolder('Campaigns');

    // Act & Assert
    $this->get(route('larasell.admin.media.index', ['folder' => 999]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('folderId', null)->has('entries.data', 1));
});

it('uploads into the folder in the URL', function () {
    // Arrange
    $campaigns = adminFolder('Campaigns');

    // Act
    $this->post(route('larasell.admin.media.store', ['folder' => $campaigns->id]), ['files' => [UploadedFile::fake()->image('boots.png')]])
        ->assertRedirect(route('larasell.admin.media.index', ['folder' => $campaigns->id]));

    // Assert
    expect(Media::query()->sole()->folder_id)->toBe($campaigns->id);
});

it('refuses an upload into a deleted folder as final, so the queue does not retry it', function () {
    // Arrange
    $campaigns = adminFolder('Campaigns');
    $campaigns->delete();

    // Act & Assert
    $this->withHeaders(['Accept' => 'application/json', 'Idempotency-Key' => (string) Str::uuid()])
        ->post(route('larasell.admin.media.store', ['folder' => $campaigns->id]), ['files' => [UploadedFile::fake()->image('boots.png')]])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['folder' => 'The folder was deleted, so the file was not uploaded.']);
    expect(Media::query()->count())->toBe(0);
});

it('creates a folder in the folder shown', function () {
    // Arrange
    $campaigns = adminFolder('Campaigns');

    // Act
    $this->post(route('larasell.admin.media.folders.store'), ['name' => ' Summer ', 'parent_id' => $campaigns->id])
        ->assertSessionHasNoErrors();

    // Assert
    expect(MediaFolder::query()->where('parent_id', $campaigns->id)->sole()->name)->toBe('Summer');
});

it('says when a folder name is taken or not allowed', function (string $name, string $message) {
    // Arrange
    adminFolder('Summer');

    // Act & Assert
    $this->post(route('larasell.admin.media.folders.store'), ['name' => $name])
        ->assertSessionHasErrors(['name' => $message]);
    expect(MediaFolder::query()->count())->toBe(1);
})->with([
    'taken, in another case' => ['SUMMER', 'A folder with that name is already there.'],
    'slash' => ['Summer/Winter', 'A folder name can\'t contain a slash or invisible characters.'],
]);

it('renames a folder, but not to a sibling\'s name', function () {
    // Arrange
    $summer = adminFolder('Summer');
    adminFolder('Winter');

    // Act
    $this->put(route('larasell.admin.media.folders.update', $summer), ['name' => 'Summer 2026'])->assertSessionHasNoErrors();

    // Assert
    expect($summer->refresh()->name)->toBe('Summer 2026');
    $this->put(route('larasell.admin.media.folders.update', $summer), ['name' => 'winter'])
        ->assertSessionHasErrors(['name' => 'A folder with that name is already there.']);
});

it('deletes an empty folder once its name is typed', function () {
    // Arrange
    $summer = adminFolder('Summer');

    // Act
    $this->delete(route('larasell.admin.media.folders.destroy', $summer), ['confirmation' => 'Summer'])
        ->assertSessionHasNoErrors();

    // Assert
    expect(MediaFolder::query()->count())->toBe(0);
});

it('refuses to delete an empty folder until its exact name is typed', function (?string $confirmation) {
    // Arrange
    $summer = adminFolder('Summer');

    // Act & Assert
    $this->delete(route('larasell.admin.media.folders.destroy', $summer), ['confirmation' => $confirmation])
        ->assertSessionHasErrors(['confirmation' => 'Type the folder\'s name to confirm.']);
    expect(MediaFolder::query()->count())->toBe(1);
})->with(['nothing' => [null], 'another case' => ['summer'], 'part of it' => ['Summe']]);

it('refuses to delete a folder with media or folders in it, before asking for its name', function (string $holds) {
    // Arrange
    $campaigns = adminFolder('Campaigns');
    $holds === 'media' ? adminMediaIn($campaigns) : adminFolder('Summer', $campaigns);

    // Act & Assert
    // The library sends a filled folder's delete without a name, so the admin learns why at once.
    $this->delete(route('larasell.admin.media.folders.destroy', $campaigns), ['confirmation' => ''])
        ->assertSessionHasErrors(['folder' => 'Only empty folders can be deleted. Delete what is in it first.'])
        ->assertSessionDoesntHaveErrors('confirmation');
    expect(MediaFolder::query()->whereKey($campaigns->id)->exists())->toBeTrue();
})->with(['media', 'folder']);

it('tells the other admins\' libraries to reload when a folder or its media change', function (Closure $change) {
    // Arrange
    $campaigns = adminFolder('Campaigns');
    Event::fake([MediaLibraryChanged::class]);

    // Act
    $change($this, $campaigns);

    // Assert
    Event::assertDispatched(MediaLibraryChanged::class);
})->with([
    'renamed' => [fn ($test, MediaFolder $folder) => $test->put(route('larasell.admin.media.folders.update', $folder), ['name' => 'Ads'])],
    'deleted' => [fn ($test, MediaFolder $folder) => $test->delete(route('larasell.admin.media.folders.destroy', $folder), ['confirmation' => 'Campaigns'])],
    'uploaded into' => [fn ($test, MediaFolder $folder) => $test->post(route('larasell.admin.media.store', ['folder' => $folder->id]), ['files' => [UploadedFile::fake()->image('a.png')]])],
]);

it('sends nothing for a change a listener refused and rolled back', function () {
    // Arrange
    $summer = adminFolder('Summer');
    $sent = 0;
    Event::listen(MediaLibraryChanged::class, function () use (&$sent): void {
        $sent++;
    });
    Event::listen(MediaFolderRenamed::class, fn () => throw new RuntimeException('Refused'));

    // Act
    $this->put(route('larasell.admin.media.folders.update', $summer), ['name' => 'Winter']);

    // Assert
    expect($sent)->toBe(0)
        ->and($summer->refresh()->name)->toBe('Summer');
});

it('broadcasts library changes on a private channel that lets only admins in', function () {
    // Arrange
    $event = new MediaLibraryChanged;
    $authorize = app(BroadcastManager::class)->driver()->getChannels()[MediaLibraryChanged::CHANNEL];

    // Act
    $channel = $event->broadcastOn();

    // Assert
    expect($channel->name)->toBe('private-larasell-admin.media')
        ->and($event->broadcastAs())->toBe('media.changed')
        ->and($authorize(Admin::query()->sole()))->toBeTrue()
        ->and($authorize(new stdClass))->toBeFalse();
});

it('makes the tree a live prop, reloaded by MediaLibraryChanged, and loads the entries as the admin scrolls', function () {
    // Act
    $response = $this->get(route('larasell.admin.media.index'), ['X-Inertia' => 'true']);

    // Assert
    $listener = ['channel' => ['name' => 'larasell-admin.media', 'type' => 'private'], 'events' => ['media.changed']];
    expect($response->json('liveProps.tree.listeners'))->toBe([$listener])
        ->and($response->json('liveProps.entries'))->toBeNull()
        ->and($response->json('scrollProps.entries.pageName'))->toBe('page');
});
