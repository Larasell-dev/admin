<?php

namespace Larasell\Admin\Data\ViewModel;

use Larasell\Larasell\Models\Customer;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CustomerDetailData extends Data
{
    public function __construct(
        public string $name,
        public string $firstName,
        public string $lastName,
    ) {}

    public static function fromModel(Customer $customer): self
    {
        return new self(
            name: $customer->fullName(),
            firstName: $customer->first_name,
            lastName: $customer->last_name,
        );
    }
}
