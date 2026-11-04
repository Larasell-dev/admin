<?php

namespace Larasell\Admin\Data\ViewModel\ContentLocale;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ContentLocaleOptionData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
    ) {}
}
