<?php

namespace Larasell\Admin\Team\Data;

use Larasell\Admin\Team\Tables\Admins;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AdminsPageData extends Data
{
    public function __construct(
        public string $createUrl,
        #[LiteralTypeScriptType('import("./index").TableResource')]
        public Admins $admins,
    ) {}
}
