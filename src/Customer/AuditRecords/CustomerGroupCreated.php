<?php

namespace Larasell\Admin\Customer\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;

/**
 * An admin created a customer group.
 *
 * @internal Its event name, customer_group.created, is stable.
 */
class CustomerGroupCreated extends AuditRecord
{
    /** @param array<string, mixed> $properties */
    public function __construct(private readonly int $id, private readonly array $properties = []) {}

    public function event(): string
    {
        return 'customer_group.created';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::BusinessCritical;
    }

    public function subject(): Subject
    {
        return new Subject('customer_group', $this->id);
    }

    public function properties(): array
    {
        return $this->properties;
    }
}
