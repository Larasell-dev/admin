<?php

namespace Larasell\Admin\Media\Data;

use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Media\AssetStatus;
use Larasell\Larasell\Media\AssetVisibility;
use Larasell\Larasell\Media\MediaType;
use Larasell\Larasell\Media\Models\Media;
use Larasell\Larasell\Translatable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A media item in the library's grid. */
#[TypeScript]
class MediaItemData extends Data
{
    public function __construct(
        public int $id,
        #[LiteralTypeScriptType('"image" | "video"')]
        public MediaType $type,
        public string $fileName,
        /** Null until its original is ready. */
        public ?string $url,
        #[LiteralTypeScriptType('"pending" | "processing" | "ready" | "failed"')]
        public AssetStatus $status,
        /** A small WebP for the grid; null for videos, and for images until the queue made it. */
        public ?string $thumbnailUrl,
        /** A ThumbHash, base64, shown while the image loads; null for videos and until the queue made it. */
        public ?string $thumbHash,
        /** In the content locale; null when it has none there. */
        public ?string $title,
        /** In the content locale; null when it has none there. */
        public ?string $alt,
        #[LiteralTypeScriptType('"public" | "private"')]
        public AssetVisibility $visibility,
        public string $showUrl,
        /** Moves it into the posted folder_id, or to the top level without one. */
        public string $moveUrl,
        /** Creates a folder from the posted name and parent_id and moves it in. */
        public string $moveToNewFolderUrl,
        /** Deletes it once the posted confirmation is "Delete media". */
        public string $destroyUrl,
    ) {}

    public static function fromModel(Media $media, Locale $locale, ?string $thumbHash = null): self
    {
        return new self(
            id: $media->id,
            type: $media->type,
            fileName: $media->original->file_name,
            url: MediaAssetData::url($media->original),
            status: $media->original->status,
            thumbnailUrl: $media->thumbnail === null ? null : MediaAssetData::url($media->thumbnail),
            thumbHash: $thumbHash,
            visibility: $media->original->visibility,
            title: self::inLocale($media->title, $locale),
            alt: self::inLocale($media->alt, $locale),
            showUrl: route('larasell.admin.media.show', $media),
            moveUrl: route('larasell.admin.media.move', $media),
            moveToNewFolderUrl: route('larasell.admin.media.move-to-new-folder', $media),
            destroyUrl: route('larasell.admin.media.destroy', $media),
        );
    }

    /** Only the locale's own text, so a field edited in it never starts in another language. */
    public static function inLocale(?Translatable $text, Locale $locale): ?string
    {
        return $text?->has($locale->value) ? $text->get($locale->value) : null;
    }
}
