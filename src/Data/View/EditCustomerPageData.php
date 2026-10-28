<?php

namespace Larasell\Admin\Data\View;

use Larasell\Admin\Data\ViewModel\CustomerDetailData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class EditCustomerPageData extends Data
{
    public function __construct(
        public CustomerDetailData $customer,
        public string $updateUrl,
        public string $indexUrl,
    ) {}
}
