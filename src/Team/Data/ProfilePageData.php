<?php

namespace Larasell\Admin\Team\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ProfilePageData extends Data
{
    public function __construct(
        public AdminData $admin,
        public string $updateUrl,
        public string $avatarUploadUrl,
    ) {}
}
