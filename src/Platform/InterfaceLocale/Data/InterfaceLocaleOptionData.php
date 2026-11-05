<?php

namespace Larasell\Admin\Platform\InterfaceLocale\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class InterfaceLocaleOptionData extends Data
{
    public function __construct(
        public string $value,
        /** Its name in itself, e.g. "Deutsch". */
        public string $label,
    ) {}
}
