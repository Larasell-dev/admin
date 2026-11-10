<?php

namespace Larasell\Admin\Customer\Data;

use Larasell\Larasell\Customers\Models\CustomerGroup;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CustomerGroupOptionData extends Data
{
    public function __construct(public int $value, public string $label) {}

    /** @return array<int, self> */
    public static function options(): array
    {
        return CustomerGroup::query()->orderBy('code')->get()
            ->map(fn (CustomerGroup $group): self => new self($group->id, $group->name->get(app()->getLocale())))
            ->all();
    }
}
