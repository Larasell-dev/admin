<?php

namespace Larasell\Admin\Product\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;

/**
 * A product changed: which attributes or relations, as the core names them.
 *
 * @internal Recorded from the core's event. Its event name, product.updated, is stable.
 */
class ProductUpdated extends AuditRecord
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function __construct(private readonly Subject $subject, private readonly array $properties) {}

    public function event(): string
    {
        return 'product.updated';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::BusinessCritical;
    }

    public function subject(): Subject
    {
        return $this->subject;
    }

    public function properties(): array
    {
        return $this->properties;
    }
}
