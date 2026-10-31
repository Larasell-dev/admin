<?php

namespace Larasell\Admin\Data\View;

use Larasell\Admin\Tables\Panel\ApiTokens;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @internal
 */
#[TypeScript]
class ApiTokensPageData extends Data
{
    public function __construct(
        public string $createUrl,
        #[LiteralTypeScriptType('import("./index").TableResource')]
        public ApiTokens $apiTokens,
    ) {}
}
