<?php

namespace Larasell\Admin\Data\ViewModel;

use Larasell\Admin\Data\ViewModel\Search\SearchData;
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
