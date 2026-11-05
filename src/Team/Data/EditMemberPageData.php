<?php

namespace Larasell\Admin\Team\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class EditMemberPageData extends Data
{
    public function __construct(
        public AdminData $member,
        public string $updateUrl,
        public string $indexUrl,
    ) {}
}
