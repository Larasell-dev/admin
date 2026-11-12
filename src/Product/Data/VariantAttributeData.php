<?php

namespace Larasell\Admin\Product\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An attribute a variant can have a value of; inheritable ones fall back to the product's. */
#[TypeScript]
class VariantAttributeData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $inheritable,
        /** Whether it takes several values. */
        public bool $multiple,
        #[LiteralTypeScriptType('"inherited" | "overridden"')]
        public string $state,
        /** @var list<int> The own values' IDs. */
        #[LiteralTypeScriptType('number[]')]
        public array $value,
        /** @var list<int> The product's values' IDs. */
        #[LiteralTypeScriptType('number[]')]
        public array $inherited,
        /** @var array<int, array{id: int, name: string}> */
        #[LiteralTypeScriptType('Array<{ id: number; name: string }>')]
        public array $values,
    ) {}
}
