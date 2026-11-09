<?php

namespace Larasell\Admin\Product\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Larasell\Catalog\Events\ProductDeleted as CoreEvent;

/**
 * A product was deleted, with the name it had, since the row is gone.
 *
 * @internal Recorded from the core's event. Its event name, product.deleted, is stable.
 */
class ProductDeleted extends AuditRecord
{
    public function __construct(private readonly CoreEvent $event) {}

    public function event(): string
    {
        return 'product.deleted';
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
        return ['name' => $this->event->name->all()];
    }
}
