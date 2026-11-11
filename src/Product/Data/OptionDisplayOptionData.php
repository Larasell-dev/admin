<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Admin\Product\Tables\Options;
use Larasell\Larasell\Catalog\Options\OptionDisplay;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** How the store can show an option, to pick from. */
#[TypeScript]
class OptionDisplayOptionData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
    ) {}

    /** @return array<int, self> */
    public static function options(): array
    {
        return array_map(fn (OptionDisplay $display): self => new self($display->value, Options::displayLabel($display)), OptionDisplay::cases());
    }
}
