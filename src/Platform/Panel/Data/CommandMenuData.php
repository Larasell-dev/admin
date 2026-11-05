<?php

namespace Larasell\Admin\Platform\Panel\Data;

use Larasell\Admin\Platform\Search\Data\SearchData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * What the command menu needs from the server, shared with every page of a signed-in admin.
 */
#[TypeScript]
class CommandMenuData extends Data
{
    public function __construct(
        /** @var list<SearchData> */
        public array $searches,
    ) {}
}
