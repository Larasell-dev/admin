<?php

namespace Larasell\Admin\Product\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OptionPageData extends Data
{
    public function __construct(
        public OptionDetailData $option,
        /** @var array<int, OptionValueRowData> In the order the store shows them. */
        public array $values,
        /** @var array<int, OptionDisplayOptionData> */
        public array $displays,
        public string $updateUrl,
        public string $destroyUrl,
        public string $indexUrl,
    ) {}
}
