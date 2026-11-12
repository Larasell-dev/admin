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
        /** @var array<int, OptionValueData> The values the product offers */
        public array $values,
        /** @var array<int, OptionValueData> The definition's values the product doesn't offer yet */
        public array $otherValues,
    ) {}
}
