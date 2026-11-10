<?php

namespace Larasell\Admin\Customer\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The page to add a group, without one, or to edit one. */
#[TypeScript]
class CustomerGroupPageData extends Data
{
    public function __construct(
        public ?CustomerGroupData $group,
        public string $saveUrl,
        public ?string $destroyUrl,
        public string $indexUrl,
    ) {}
}
