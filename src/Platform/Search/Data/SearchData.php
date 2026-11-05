<?php

namespace Larasell\Admin\Platform\Search\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A search the command menu offers as a command.
 */
#[TypeScript]
class SearchData extends Data
{
    public function __construct(
        public string $id,
        public string $label,
        /** Icon case name, e.g. "ShoppingBag"; @larasell-dev/ui/icons exports it with a Line suffix. */
        public string $icon,
        public string $placeholder,
        public string $url,
    ) {}
}
