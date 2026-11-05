<?php

namespace Larasell\Admin\Team\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateAdminPageData extends Data
{
    public function __construct(
        public string $storeUrl,
        public string $indexUrl,
    ) {}
}
