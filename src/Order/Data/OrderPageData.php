<?php

namespace Larasell\Admin\Order\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OrderPageData extends Data
{
    public function __construct(
        public OrderDetailData $order,
        public string $indexUrl,
    ) {}
}
