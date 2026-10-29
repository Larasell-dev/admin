<?php

namespace Larasell\Admin\Data\View\Orders;

use Larasell\Admin\Data\ViewModel\Orders\OrderDetailData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OrderPageData extends Data
{
    public function __construct(
        public OrderDetailData $order,
        public string $indexUrl,
        /** The customer who placed the order, or null for a guest order or while the customers are turned off. */
        public ?string $customerUrl,
    ) {}
}
