<?php

namespace Larasell\Admin\Product\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProductOptionData extends Data
{
    public function __construct(
        public int $definitionId,
        public string $name,
        /** The values the product offers. @var array<int, OptionValueData> */
        public array $values,
        /** The definition's values the product doesn't offer yet. @var array<int, OptionValueData> */
        public array $otherValues,
        public string $addValueUrl,
    ) {}
}
