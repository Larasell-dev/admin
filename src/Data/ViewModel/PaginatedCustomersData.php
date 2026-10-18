<?php

namespace Larasell\Admin\Data\ViewModel;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PaginatedCustomersData extends PaginatedData
{
    /** @var array<int, CustomerData> */
    public array $data;
}
