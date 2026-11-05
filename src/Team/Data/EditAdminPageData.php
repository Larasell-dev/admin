<?php

namespace Larasell\Admin\Team\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class EditAdminPageData extends Data
{
    public function __construct(
        // Not "admin", which every page shares as the signed-in admin.
        public AdminData $editedAdmin,
        public string $updateUrl,
        public string $indexUrl,
    ) {}
}
