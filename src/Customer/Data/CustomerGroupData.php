<?php

namespace Larasell\Admin\Customer\Data;

use Larasell\Larasell\Customers\Models\CustomerGroup;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CustomerGroupData extends Data
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public ?string $description,
    ) {}

    public static function fromModel(CustomerGroup $group): self
    {
        return new self($group->id, $group->code, $group->name->get(app()->getLocale()), $group->description);
    }
}
