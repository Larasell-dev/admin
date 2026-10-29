<?php

namespace Larasell\Admin\Data\ViewModel\Orders;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A line of the order's summary, e.g. "VAT (19%)" and its formatted amount. */
#[TypeScript]
class AmountData extends Data
{
    public function __construct(
        public string $label,
        public string $amount,
    ) {}
}
