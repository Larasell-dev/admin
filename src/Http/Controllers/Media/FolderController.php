<?php

namespace Larasell\Admin\Http\Controllers\Media;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Actions\Media\DeleteFolder;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Admin\Tables\Panel\Media\Media;
use Laravel\Head\Facades\Head;

class FolderController
{
    public function show(Folder $folder): Response
    {
        $table = Media::make($folder);

        Head::title($table->title());

        return Inertia::render('Media/Index', [
            'table' => $table,
        ]);
    }

    /**
     * Deletes the folder with everything in it, once its name was typed to confirm, and
     * returns to the page it was deleted on.
     *
     * @throws ValidationException
     */
    public function destroy(Request $request, Folder $folder, DeleteFolder $deleteFolder): RedirectResponse
    {
        if ($request->input('confirmation') !== $folder->name) {
            throw ValidationException::withMessages([
                'confirmation' => "Type \"{$folder->name}\" to confirm.",
            ]);
        }

        $deleteFolder->handle($folder);

        return back();
    }
}
