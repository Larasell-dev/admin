<?php

namespace Larasell\Admin\Customer\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;

/**
 * An admin deleted an empty customer group.
 *
 * @internal Its event name, customer_group.deleted, is stable.
 */
class CustomerGroupDeleted extends AuditRecord
{
    /** @param array<string, mixed> $properties */
    public function __construct(private readonly int $id, private readonly array $properties = []) {}

    public function event(): string
    {
        return 'customer_group.deleted';
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
