<?php

namespace Larasell\Admin\Http\Controllers\Media;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Larasell\Admin\Actions\Media\CreateFolder;
use Larasell\Admin\Actions\Media\DeleteFolder;
use Larasell\Admin\Models\Media\Folder;
use Larasell\Admin\Tables\Panel\Media\Media;
use Laravel\Head\Facades\Head;
use Symfony\Component\HttpFoundation\Response;

class FolderController
{
    /**
     * Shows the media in a folder. A folder that no longer exists, such as one deleted in
     * another tab, explains itself on the media page instead of a bare 404 page.
     */
    public function show(Request $request, string $folder): Response
    {
        $folder = Folder::query()->find($folder);

        if ($folder === null) {
            Head::title('Folder not found');

            // Inertia still renders a page that comes with an error status.
            return Inertia::render('Media/Index', [
                'table' => null,
            ])->toResponse($request)->setStatusCode(404);
        }

        $table = Media::make($folder);

        Head::title($table->title());

        return Inertia::render('Media/Index', [
            'table' => $table,
            'createFolderUrl' => route('larasell.admin.media.folders.store'),
            'uploadUrl' => route('larasell.admin.media.product-images.store'),
            'folderId' => $folder->id,
        ])->toResponse($request);
    }

    /**
     * Creates a folder inside the folder given as parent_id, or at the top level without one,
     * and returns to the page the folder was created on. CreateFolder validates the name.
     */
    public function store(Request $request, CreateFolder $createFolder): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists(Folder::class, 'id')],
        ]);

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        $createFolder->handle((string) $request->input('name'), $parentId);

        return $parentId === null
            ? redirect()->route('larasell.admin.media.index')
            : redirect()->route('larasell.admin.media.folders.show', $parentId);
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
