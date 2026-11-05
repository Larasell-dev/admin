<?php

namespace Larasell\Admin\Team\Data;

use Larasell\Admin\Team\Tables\Members;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TeamPageData extends Data
{
    public function __construct(
        public string $createUrl,
        #[LiteralTypeScriptType('import("./index").TableResource')]
        public Members $members,
    ) {}
}
