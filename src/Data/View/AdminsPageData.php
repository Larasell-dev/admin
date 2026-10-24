<?php

namespace Larasell\Admin\Data\View;

use Larasell\Admin\Tables\Panel\Admins;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AdminsPageData extends Data
{
    public function __construct(
        #[LiteralTypeScriptType('import("./index").TableResource')]
        public Admins $admins,
    ) {}
}
