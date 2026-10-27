<?php

namespace Larasell\Admin\Data\ViewModel;

use Larasell\Larasell\Models\ProductImage;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProductImageData extends Data
{
    public function __construct(
        public int $id,
        public string $url,
        /** The stored file's name, which uploading picks, so it's the name of the file that opens. */
        public string $filename,
        /** Describes the image to those who can't see it; null until it's given one. */
        public ?string $alt,
    ) {}

    public static function fromModel(ProductImage $image): self
    {
        return new self($image->id, $image->url(), basename($image->file), $image->alt);
    }
}
