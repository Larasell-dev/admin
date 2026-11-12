<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Larasell\Catalog\Options\Models\OptionDefinitionValue;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OptionValueData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        /** A colour such as #000000. */
        public ?string $swatch,
    ) {}

    public static function fromValue(OptionDefinitionValue $value, Locale $locale): self
    {
        return new self($value->id, $value->name->get($locale->value), $value->swatch);
    }
}
