<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Media\Actions\MoveMediaToNewFolder;
use Larasell\Admin\Media\Actions\MoveMediaToNewFolderPayload;
use Larasell\Admin\Media\Events\MediaLibraryChanged;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Media\Actions\CreateMediaFolder;
use Larasell\Larasell\Media\Actions\CreateMediaFolderPayload;
use Larasell\Larasell\Media\Actions\UploadMedia;
use Larasell\Larasell\Media\Actions\UploadMediaPayload;
use Larasell\Larasell\Media\Events\MediaMoved;
use Larasell\Larasell\Media\MediaFolderId;
use Larasell\Larasell\Media\MediaFolderName;
use Larasell\Larasell\Media\MediaId;
use Larasell\Larasell\Media\Models\Media;
use Larasell\Larasell\Media\Models\MediaFolder;

beforeEach(function () {
    Storage::fake('media');
    Storage::fake('private-media');
    config(['larasell.media.disks' => ['public' => 'media', 'private' => 'private-media']]);
    test()->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

function moveFolder(string $name, ?MediaFolder $parent = null): MediaFolder
{
    return app(CreateMediaFolder::class)->handle(new CreateMediaFolderPayload(new MediaFolderName($name), $parent === null ? null : MediaFolderId::of($parent)));
}

function moveMediaIn(?MediaFolder $folder): Media
{
    return app(UploadMedia::class)->handle(new UploadMediaPayload(UploadedFile::fake()->image('boots.png'), folderId: $folder === null ? null : MediaFolderId::of($folder)));
}

it('moves an item into a folder, and back to the top level', function () {
    // Arrange
    $summer = moveFolder('Summer');
    $media = moveMediaIn(null);

    // Act
    $this->post(route('larasell.admin.media.move', $media), ['folder_id' => $summer->id])->assertSessionHasNoErrors();
    $intoFolder = $media->refresh()->folder_id;
    $this->post(route('larasell.admin.media.move', $media), ['folder_id' => null])->assertSessionHasNoErrors();

    // Assert
    expect($intoFolder)->toBe($summer->id)
        ->and($media->refresh()->folder_id)->toBeNull();
});

it('takes a moved item off the page it was on, and shows the last page when that one empties', function () {
    // Arrange
    // With the folder, 49 entries: the oldest item is alone on page 2.
    $summer = moveFolder('Summer');
    $items = collect(range(1, 48))->map(fn () => moveMediaIn(null));
    $oldest = $items->first();

    // Act
    $response = $this->from(route('larasell.admin.media.index', ['page' => 2]))
        ->post(route('larasell.admin.media.move', $oldest), ['folder_id' => $summer->id]);

    // Assert
    $response->assertRedirect(route('larasell.admin.media.index', ['page' => 2]));
    $this->get(route('larasell.admin.media.index', ['page' => 2]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 48)
            ->where('entries.data.0.folder.mediaCount', 1)
            ->where('entries.data.47.media.id', $items[1]->id));
});

it('says the folder no longer exists when it was deleted meanwhile, and moves nothing', function () {
    // Arrange
    $summer = moveFolder('Summer');
    $media = moveMediaIn(null);
    $summer->delete();

    // Act & Assert
    $this->post(route('larasell.admin.media.move', $media), ['folder_id' => $summer->id])
        ->assertSessionHasErrors(['folder' => 'The folder no longer exists.']);
    expect($media->refresh()->folder_id)->toBeNull();
});

it('tells the other admins\' libraries to reload after a move', function () {
    // Arrange
    $summer = moveFolder('Summer');
    $media = moveMediaIn(null);
    Event::fake([MediaLibraryChanged::class]);

    // Act
    $this->post(route('larasell.admin.media.move', $media), ['folder_id' => $summer->id]);

    // Assert
    Event::assertDispatched(MediaLibraryChanged::class);
});

it('creates a folder in the folder shown and moves the item into it', function () {
    // Arrange
    $campaigns = moveFolder('Campaigns');
    $media = moveMediaIn($campaigns);

    // Act
    $this->post(route('larasell.admin.media.move-to-new-folder', $media), ['name' => 'Summer', 'parent_id' => $campaigns->id])
        ->assertSessionHasNoErrors();

    // Assert
    $summer = MediaFolder::query()->where('name', 'Summer')->sole();
    expect($summer->parent_id)->toBe($campaigns->id)
        ->and($media->refresh()->folder_id)->toBe($summer->id);
});

it('neither creates the folder nor moves the item when the name is taken', function () {
    // Arrange
    moveFolder('Summer');
    $media = moveMediaIn(null);

    // Act & Assert
    $this->post(route('larasell.admin.media.move-to-new-folder', $media), ['name' => 'summer'])
        ->assertSessionHasErrors(['name' => 'A folder with that name is already there.']);
    expect(MediaFolder::query()->count())->toBe(1)
        ->and($media->refresh()->folder_id)->toBeNull();
});

it('says the folder no longer exists when the one to create it in was deleted', function () {
    // Arrange
    $campaigns = moveFolder('Campaigns');
    $media = moveMediaIn($campaigns);
    $media->update(['folder_id' => null]);
    $campaigns->delete();

    // Act & Assert
    $this->post(route('larasell.admin.media.move-to-new-folder', $media), ['name' => 'Summer', 'parent_id' => $campaigns->id])
        ->assertSessionHasErrors(['folder' => 'The folder no longer exists.']);
    expect(MediaFolder::query()->count())->toBe(0);
});

it('keeps no folder when the move after creating it fails', function () {
    // Arrange
    $media = moveMediaIn(null);
    Event::listen(MediaMoved::class, fn () => throw new RuntimeException('Refused'));

    // Act & Assert
    expect(fn () => app(MoveMediaToNewFolder::class)->handle(new MoveMediaToNewFolderPayload(MediaId::of($media), new MediaFolderName('Summer'), null)))
        ->toThrow(RuntimeException::class, 'Refused');
    expect(MediaFolder::query()->count())->toBe(0)
        ->and($media->refresh()->folder_id)->toBeNull();
});
