<?php

namespace Larasell\Admin\Product\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A combination of option values, one of each of the product's options. */
#[TypeScript]
class CombinationData extends Data
{
    public function __construct(
        /** @var int[] Option definition value IDs */
        public array $valueIds,
        public string $label,
    ) {}
}
