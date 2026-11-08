<?php

namespace Larasell\Admin\Media\Data;

use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Media\AssetVisibility;
use Larasell\Larasell\Media\MediaType;
use Larasell\Larasell\Media\Models\Media;
use Larasell\Larasell\Media\Models\MediaAsset;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MediaDetailData extends Data
{
    public function __construct(
        public int $id,
        #[LiteralTypeScriptType('"image" | "video"')]
        public MediaType $type,
        public string $fileName,
        /** The original file's. */
        public string $url,
        /** In the content locale; null when it has none there. */
        public ?string $title,
        /** In the content locale; null when it has none there. */
        public ?string $description,
        /** In the content locale; null when it has none there. */
        public ?string $alt,
        #[LiteralTypeScriptType('"public" | "private"')]
        public AssetVisibility $visibility,
        /** @var array<int, MediaAssetData> */
        public array $assets,
    ) {}

    public static function fromModel(Media $media, Locale $locale): self
    {
        return new self(
            id: $media->id,
            type: $media->type,
            fileName: $media->original->file_name,
            url: MediaAssetData::url($media->original),
            visibility: $media->original->visibility,
            title: MediaItemData::inLocale($media->title, $locale),
            description: MediaItemData::inLocale($media->description, $locale),
            alt: MediaItemData::inLocale($media->alt, $locale),
            assets: $media->assets->map(fn (MediaAsset $asset): MediaAssetData => MediaAssetData::fromModel($asset))->all(),
        );
    }
}
