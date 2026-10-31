<?php

namespace Larasell\Admin\Data\View;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @internal
 */
#[TypeScript]
class CreateApiTokenPageData extends Data
{
    public function __construct(
        public string $storeUrl,
        public string $indexUrl,
        /** @var array<int, array{value: string, label: string}> */
        #[LiteralTypeScriptType('Array<{ value: string; label: string }>')]
        public array $expiryOptions,
        public string $defaultExpiresIn,
    ) {}
}
