<?php

namespace Larasell\Admin\Media\Data;

use Larasell\Larasell\Media\AssetVisibility;
use Larasell\Larasell\Media\Models\MediaAsset;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One file of a media item, e.g. its original. */
#[TypeScript]
class MediaAssetData extends Data
{
    public function __construct(
        /** "original" for the file as uploaded. */
        public string $role,
        #[LiteralTypeScriptType('"public" | "private"')]
        public AssetVisibility $visibility,
        public string $fileName,
        public string $mimeType,
        /** In bytes. */
        public int $size,
        /** In pixels; null for videos. */
        public ?int $width,
        public ?int $height,
        /** The file's own URL when public; the panel's, behind its sign-in, when private. */
        public string $url,
    ) {}

    public static function fromModel(MediaAsset $asset): self
    {
        return new self(
            role: $asset->role,
            visibility: $asset->visibility,
            fileName: $asset->file_name,
            mimeType: $asset->mime_type,
            size: $asset->size,
            width: $asset->width,
            height: $asset->height,
            url: self::url($asset),
        );
    }

    /** Private files never get a URL of their own here; the panel streams them to signed-in admins. */
    public static function url(MediaAsset $asset): string
    {
        return $asset->visibility === AssetVisibility::Public
            ? $asset->url()
            : route('larasell.admin.media.file', $asset->role === MediaAsset::ORIGINAL ? [$asset->media_id] : [$asset->media_id, $asset->role]);
    }
}
