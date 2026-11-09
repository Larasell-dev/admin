<?php

namespace Larasell\Admin\Product\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Larasell\Catalog\Events\ProductUpdated as CoreEvent;

/**
 * A product changed: which attributes or relations, as the core names them.
 *
 * @internal Recorded from the core's event. Its event name, product.updated, is stable.
 */
class ProductUpdated extends AuditRecord
{
    public function __construct(private readonly CoreEvent $event) {}

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
        return new Subject('product', $this->event->productId->value);
    }

    public function properties(): array
    {
        return ['changed' => $this->event->changed];
    }
}
