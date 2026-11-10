<?php

namespace Larasell\Admin\Customer\Data;

use Larasell\Larasell\Customers\Models\CustomerGroup;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CustomerGroupMembershipData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $code,
        public string $destroyUrl,
    ) {}

    public static function fromGroup(int $customerId, CustomerGroup $group): self
    {
        return new self(
            id: $group->id,
            name: $group->name->get(app()->getLocale()),
            code: $group->code,
            destroyUrl: route('larasell.admin.customers.groups.destroy', [$customerId, $group]),
        );
    }
}
