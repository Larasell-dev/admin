<?php

namespace Larasell\Admin\Product\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Larasell\Catalog\Events\ProductCreated as CoreEvent;

/**
 * A product was created, with its name.
 *
 * @internal Recorded from the core's event. Its event name, product.created, is stable.
 */
class ProductCreated extends AuditRecord
{
    /**
     * @param  array<string, string>  $name  The stored name per locale, read back after the change.
     */
    public function __construct(private readonly CoreEvent $event, private readonly array $name) {}

    public function event(): string
    {
        return 'product.created';
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
        return ['name' => $this->name];
    }
}
