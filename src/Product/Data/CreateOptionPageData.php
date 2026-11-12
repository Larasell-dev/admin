<?php

namespace Larasell\Admin\Product\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateOptionPageData extends Data
{
    public function __construct(
        public string $storeUrl,
        public string $indexUrl,
        /** @var array<int, OptionDisplayOptionData> */
        public array $displays,
    ) {}
}
