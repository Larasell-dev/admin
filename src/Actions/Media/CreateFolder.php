<?php

namespace Larasell\Admin\Actions\Media;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Models\Media\Folder;

class CreateFolder
{
    /**
     * Creates a folder inside the parent folder, given as a model or its id, or a top-level folder when the parent is null.
     *
     * @throws ValidationException
     * @throws ModelNotFoundException<Folder>
     */
    public function handle(string $name, Folder|int|null $parent): Folder
    {
        $validated = Validator::make(['name' => $name], [
            'name' => ['required', 'string', 'max:255'],
        ])->validate();

        if (is_int($parent)) {
            $parent = Folder::query()->findOrFail($parent);
        }

        return Folder::query()->create([
            'name' => $validated['name'],
            'parent_id' => $parent?->getKey(),
        ]);
    }
}
