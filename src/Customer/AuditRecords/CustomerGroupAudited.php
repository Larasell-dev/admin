<?php

namespace Larasell\Admin\Customer\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Larasell\Customers\CustomerGroupId;

/**
 * A customer group was created, changed or deleted.
 *
 * @internal Recorded from the core's customer events by AuditCustomerChanges. Its event names,
 * e.g. customer_group.created, are stable.
 */
class CustomerGroupAudited extends AuditRecord
{
    /** @param array<string, mixed> $properties */
    public function __construct(
        private readonly string $event,
        private readonly CustomerGroupId $groupId,
        private readonly array $properties = [],
    ) {}

    public function event(): string
    {
        return $this->event;
    }

    public function category(): AuditCategory
    {
        return AuditCategory::BusinessCritical;
    }

    public function subject(): Subject
    {
        return new Subject('customer_group', $this->groupId->value);
    }

    public function properties(): array
    {
        return $this->properties;
    }
}
