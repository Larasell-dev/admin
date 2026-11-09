<?php

namespace Larasell\Admin\Media\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Media\Http\Requests\MediaFolderRequest;
use Larasell\Larasell\Media\Actions\CreateMediaFolder;
use Larasell\Larasell\Media\Actions\CreateMediaFolderPayload;
use Larasell\Larasell\Media\Actions\DeleteMediaFolder;
use Larasell\Larasell\Media\Actions\RenameMediaFolder;
use Larasell\Larasell\Media\Actions\RenameMediaFolderPayload;
use Larasell\Larasell\Media\Exceptions\MediaFolderNameTaken;
use Larasell\Larasell\Media\Exceptions\MediaFolderNotEmpty;
use Larasell\Larasell\Media\Exceptions\MediaFolderNotFound;
use Larasell\Larasell\Media\MediaFolderId;
use Larasell\Larasell\Media\Models\MediaFolder;

/** Creates, renames and deletes the library's folders; the core's actions keep the tree sound. */
class MediaFolderController
{
    public function store(MediaFolderRequest $request, CreateMediaFolder $create): RedirectResponse
    {
        try {
            $create->handle(new CreateMediaFolderPayload($request->name(), $request->parentId()));
        } catch (MediaFolderNameTaken) {
            throw self::nameTaken();
        } catch (MediaFolderNotFound) {
            throw ValidationException::withMessages(['name' => __('The folder to create it in was deleted.')]);
        }

        return back();
    }

    public function update(MediaFolderRequest $request, MediaFolder $folder, RenameMediaFolder $rename): RedirectResponse
    {
        try {
            $rename->handle(MediaFolderId::of($folder), new RenameMediaFolderPayload($request->name()));
        } catch (MediaFolderNameTaken) {
            throw self::nameTaken();
        }

        return back();
    }

    public function destroy(Request $request, MediaFolder $folder, DeleteMediaFolder $delete): RedirectResponse
    {
        try {
            // Emptiness first, so a filled folder is refused for that and not for a missing confirmation.
            if ($folder->media()->exists() || $folder->children()->exists()) {
                throw self::notEmpty();
            }

            if ($request->input('confirmation') !== $folder->name) {
                throw ValidationException::withMessages(['confirmation' => __('Type the folder\'s name to confirm.')]);
            }

            // The action checks again under a lock, in case something was put in meanwhile.
            $delete->handle(MediaFolderId::of($folder));
        } catch (MediaFolderNotEmpty) {
            throw self::notEmpty();
        }

        return back();
    }

    private static function notEmpty(): ValidationException
    {
        return ValidationException::withMessages(['folder' => __('Only empty folders can be deleted. Delete what is in it first.')]);
    }

    private static function nameTaken(): ValidationException
    {
        return ValidationException::withMessages(['name' => __('A folder with that name is already there.')]);
    }
}
