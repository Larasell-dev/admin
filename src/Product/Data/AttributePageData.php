<?php

namespace Larasell\Admin\Product\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AttributePageData extends Data
{
    public function __construct(
        public AttributeDetailData $attribute,
        /** @var array<int, AttributeValueRowData> In the order products show them. */
        public array $values,
        /** @var array<int, AttributeScopeOptionData> */
        public array $scopes,
        public string $updateUrl,
        public string $destroyUrl,
        public string $indexUrl,
    ) {}
}
