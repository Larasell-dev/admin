<?php

namespace Larasell\Admin\Media\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Media\Actions\MoveMediaToNewFolder;
use Larasell\Admin\Media\Actions\MoveMediaToNewFolderPayload;
use Larasell\Admin\Media\Http\Requests\MediaFolderRequest;
use Larasell\Larasell\Media\Actions\MoveMedia;
use Larasell\Larasell\Media\Actions\MoveMediaPayload;
use Larasell\Larasell\Media\Exceptions\MediaFolderNameTaken;
use Larasell\Larasell\Media\Exceptions\MediaFolderNotFound;
use Larasell\Larasell\Media\MediaFolderId;
use Larasell\Larasell\Media\MediaId;
use Larasell\Larasell\Media\Models\Media;

/**
 * Moves a media item into another folder, or into a new one, e.g. when the admin drops it there.
 * Back to the page it came from: the index takes the item off it, and shows the last page when it emptied.
 */
class MediaMoveController
{
    public function move(Request $request, Media $media, MoveMedia $move): RedirectResponse
    {
        $request->validate(['folder_id' => ['nullable', 'integer', 'min:1']]);
        $folderId = $request->filled('folder_id') ? new MediaFolderId($request->integer('folder_id')) : null;

        try {
            $move->handle(new MoveMediaPayload([MediaId::of($media)], $folderId));
        } catch (MediaFolderNotFound) {
            throw self::folderGone();
        }

        return back();
    }

    public function moveToNewFolder(MediaFolderRequest $request, Media $media, MoveMediaToNewFolder $move): RedirectResponse
    {
        try {
            $move->handle(new MoveMediaToNewFolderPayload(MediaId::of($media), $request->name(), $request->parentId()));
        } catch (MediaFolderNameTaken) {
            throw ValidationException::withMessages(['name' => __('A folder with that name is already there.')]);
        } catch (MediaFolderNotFound) {
            throw self::folderGone();
        }

        return back();
    }

    private static function folderGone(): ValidationException
    {
        return ValidationException::withMessages(['folder' => __('The folder no longer exists.')]);
    }
}
