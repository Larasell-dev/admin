<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Admin\Product\Tables\Attributes;
use Larasell\Larasell\Catalog\AttributeScope;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** What an attribute can describe, to pick from. */
#[TypeScript]
class AttributeScopeOptionData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
    ) {}

    /** @return array<int, self> */
    public static function options(): array
    {
        return array_map(fn (AttributeScope $scope): self => new self($scope->value, Attributes::scopeLabel($scope)), AttributeScope::cases());
    }
}
