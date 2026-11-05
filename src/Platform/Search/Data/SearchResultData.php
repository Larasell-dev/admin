<?php

namespace Larasell\Admin\Platform\Search\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A row of a search in the command menu, which opens the url.
 */
#[TypeScript]
class SearchResultData extends Data
{
    public function __construct(
        public int $id,
        public string $label,
        public string $url,
        /** Null when the label is what matched. */
        public ?SearchMatchData $match,
    ) {}
}
