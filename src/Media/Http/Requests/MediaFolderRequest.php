<?php

namespace Larasell\Admin\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Larasell\Larasell\Media\Exceptions\InvalidMediaFolderName;
use Larasell\Larasell\Media\MediaFolderId;
use Larasell\Larasell\Media\MediaFolderName;

/** A folder's name, and for a new one the folder it goes in. */
class MediaFolderRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:'.MediaFolderName::MAX_LENGTH],
            'parent_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function name(): MediaFolderName
    {
        try {
            return new MediaFolderName($this->string('name')->toString());
        } catch (InvalidMediaFolderName) {
            // The rules above catch the rest; what's left is a slash or an invisible character.
            throw ValidationException::withMessages(['name' => __('A folder name can\'t contain a slash or invisible characters.')]);
        }
    }

    public function parentId(): ?MediaFolderId
    {
        return $this->filled('parent_id') ? new MediaFolderId($this->integer('parent_id')) : null;
    }
}
