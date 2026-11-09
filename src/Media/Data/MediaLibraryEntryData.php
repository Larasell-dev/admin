<?php

namespace Larasell\Admin\Media\Data;

use Larasell\Admin\Media\Models\MediaLibraryEntry;
use Larasell\Chronicle\Exceptions\StructuredLogicException;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An entry of the library: either a folder or a media item, the other one null. */
#[TypeScript]
class MediaLibraryEntryData extends Data
{
    public function __construct(
        /** Unique among both, e.g. "folder-3" or "media-7", for React and for merging pages. */
        public string $key,
        public ?MediaFolderData $folder,
        public ?MediaItemData $media,
    ) {}

    /** The entry needs its folder, with media_count and children_count, or its media loaded. */
    public static function fromModel(MediaLibraryEntry $entry, Locale $locale, ?string $thumbHash): self
    {
        if ($entry->folder !== null) {
            return new self(key: "folder-{$entry->folder->id}", folder: MediaFolderData::fromModel($entry->folder), media: null);
        }

        $media = $entry->media ?? throw new StructuredLogicException(
            errorCode: 'larasell-admin.media.entry-without-item',
            message: 'A library entry needs its folder or its media loaded.',
        );

        return new self(key: "media-{$media->id}", folder: null, media: MediaItemData::fromModel($media, $locale, $thumbHash));
    }
}
