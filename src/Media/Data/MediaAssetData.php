<?php

namespace Larasell\Admin\Media\Data;

use Larasell\Larasell\Media\AssetStatus;
use Larasell\Larasell\Media\AssetVisibility;
use Larasell\Larasell\Media\MediaProviders;
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
        #[LiteralTypeScriptType('"pending" | "processing" | "ready" | "failed"')]
        public AssetStatus $status,
        /** The file's own URL when public; the panel's, behind its sign-in, when private; null until it is ready. */
        public ?string $url,
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
            status: $asset->status,
            url: self::url($asset),
        );
    }

    /** Private files never get a URL of their own here; the panel hands them to signed-in admins. */
    public static function url(MediaAsset $asset): ?string
    {
        if ($asset->status !== AssetStatus::Ready) {
            return null;
        }

        // A provider that keeps its bytes to itself, e.g. Mux, has nothing to stream through the
        // panel: its signed URL, which expires, goes to the signed-in admin instead.
        if ($asset->visibility === AssetVisibility::Private && ! app(MediaProviders::class)->provider($asset->provider)->readable()) {
            return $asset->temporaryUrl(now()->addHours(6));
        }

        return $asset->visibility === AssetVisibility::Public
            ? $asset->url()
            : route('larasell.admin.media.file', $asset->role === MediaAsset::ORIGINAL ? [$asset->media_id] : [$asset->media_id, $asset->role]);
    }
}
