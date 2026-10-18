<?php

namespace Larasell\Admin\Data\View;

use Larasell\Admin\Data\ViewModel\PaginatedCustomersData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CustomersPageData extends Data
{
    public function __construct(
        public PaginatedCustomersData $customers,
    ) {}
}
