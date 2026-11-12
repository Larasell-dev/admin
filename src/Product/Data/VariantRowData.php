<?php

namespace Larasell\Admin\Product\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VariantRowData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $sku,
        public bool $visible,
        public bool $isDefault,
        public string $editUrl,
        public string $deleteUrl,
    ) {}
}
