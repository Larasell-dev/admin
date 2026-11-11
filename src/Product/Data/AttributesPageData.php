<?php

namespace Larasell\Admin\Product\Data;

use Larasell\Admin\Product\Tables\Attributes;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AttributesPageData extends Data
{
    public function __construct(
        public string $createUrl,
        #[LiteralTypeScriptType('import("./index").TableResource')]
        public Attributes $attributes,
    ) {}
}
