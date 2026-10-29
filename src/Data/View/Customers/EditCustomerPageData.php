<?php

namespace Larasell\Admin\Data\View\Customers;

use Larasell\Admin\Data\ViewModel\Customers\CustomerDetailData;
use Larasell\Admin\Tables\Customers\CustomerOrders;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class EditCustomerPageData extends Data
{
    public function __construct(
        public CustomerDetailData $customer,
        public string $updateUrl,
        public string $indexUrl,
        #[LiteralTypeScriptType('import("./index").TableResource')]
        public CustomerOrders $orders,
    ) {}
}
