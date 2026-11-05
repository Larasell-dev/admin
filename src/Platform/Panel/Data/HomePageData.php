<?php

namespace Larasell\Admin\Platform\Panel\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class HomePageData extends Data
{
    public function __construct(
        public ?string $createProductUrl,
        public ?string $createCategoryUrl,
        public string $inviteAdminUrl,
        public string $helpEmail,
    ) {}
}
