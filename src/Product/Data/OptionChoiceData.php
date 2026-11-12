<?php

namespace Larasell\Admin\Product\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An option definition to add to a product, with the values to pick from. */
#[TypeScript]
class OptionChoiceData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        /** @var array<int, OptionValueData> */
        public array $values,
    ) {}
}
