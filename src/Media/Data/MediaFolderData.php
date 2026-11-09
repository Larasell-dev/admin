<?php

namespace Larasell\Admin\Media\Data;

use Larasell\Larasell\Media\Models\MediaFolder;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A folder in the library's grid, with what it holds, so an admin sees whether it can be deleted. */
#[TypeScript]
class MediaFolderData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $url,
        public int $mediaCount,
        public int $folderCount,
        public string $renameUrl,
        public string $destroyUrl,
    ) {}

    /** The folder needs its media_count and children_count loaded. */
    public static function fromModel(MediaFolder $folder): self
    {
        return new self(
            id: $folder->id,
            name: $folder->name,
            url: route('larasell.admin.media.index', ['folder' => $folder->id]),
            mediaCount: (int) $folder->getAttribute('media_count'),
            folderCount: (int) $folder->getAttribute('children_count'),
            renameUrl: route('larasell.admin.media.folders.update', $folder),
            destroyUrl: route('larasell.admin.media.folders.destroy', $folder),
        );
    }
}
