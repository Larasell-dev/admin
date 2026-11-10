<?php

namespace Larasell\Admin\Customer\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;

/**
 * An admin created a customer by hand.
 *
 * @internal Its event name, customer.created, is stable.
 */
class CustomerCreated extends AuditRecord
{
    /** @param array<string, mixed> $properties */
    public function __construct(private readonly int $id, private readonly array $properties = []) {}

    public function event(): string
    {
        return 'customer.created';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::BusinessCritical;
    }

    public function subject(): Subject
    {
        return new Subject('customer', $this->id);
    }

    public function properties(): array
    {
        return $this->properties;
    }
}
