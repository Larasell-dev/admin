<?php

namespace Larasell\Admin\SalesChannel\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SalesChannelLinkData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $url,
    ) {}
}
