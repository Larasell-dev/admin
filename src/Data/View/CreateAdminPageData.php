<?php

namespace Larasell\Admin\Data\View;

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
