<?php

namespace Larasell\Admin\Data\ViewModel;

use Larasell\Larasell\Models\Customer;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CustomerData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $ordersCount,
        public ?string $createdAt,
    ) {}

    public static function fromModel(Customer $customer): self
    {
        return new self(
            id: $customer->id,
            name: $customer->fullName(),
            ordersCount: $customer->orders_count,
            createdAt: $customer->getAttribute('created_at')?->diffForHumans(),
        );
    }
}
