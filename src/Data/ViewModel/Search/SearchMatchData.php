<?php

namespace Larasell\Admin\Data\ViewModel\Search;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What a search matched besides the result's label, e.g. the SKU "BT-100".
 */
#[TypeScript]
class SearchMatchData extends Data
{
    public function __construct(
        public string $label,
        public string $value,
    ) {}
}
