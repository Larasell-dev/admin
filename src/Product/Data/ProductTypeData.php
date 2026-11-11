<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Larasell\Catalog\ProductType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A product's type, which decides which of the form's sections apply. */
#[TypeScript]
class ProductTypeData extends Data
{
    public function __construct(
        public string $key,
        public string $label,
        /** @var string[] e.g. "stock", "shipping", "dimensions" */
        public array $capabilities,
    ) {}

    public static function fromType(ProductType $type): self
    {
        return new self($type->key(), __($type->label()), $type->capabilities());
    }
}
