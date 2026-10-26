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
    ) {}

    public static function fromModel(ProductImage $image): self
    {
        return new self($image->id, $image->url());
    }
}
