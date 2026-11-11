<?php

namespace Larasell\Admin\Product\Listeners;

use Larasell\Admin\Platform\Audit\Origins;
use Larasell\Admin\Product\AuditRecords\ProductAttributeCreated as AttributeCreated;
use Larasell\Admin\Product\AuditRecords\ProductAttributeDeleted as AttributeDeleted;
use Larasell\Admin\Product\AuditRecords\ProductAttributeUpdated as AttributeUpdated;
use Larasell\Admin\Product\AuditRecords\ProductAttributeValueCreated as ValueCreated;
use Larasell\Admin\Product\AuditRecords\ProductAttributeValueDeleted as ValueDeleted;
use Larasell\Admin\Product\AuditRecords\ProductAttributeValueUpdated as ValueUpdated;
use Larasell\Larasell\Audit\Auditor;
use Larasell\Larasell\Catalog\Events\ProductAttributeCreated;
use Larasell\Larasell\Catalog\Events\ProductAttributeDeleted;
use Larasell\Larasell\Catalog\Events\ProductAttributeUpdated;
use Larasell\Larasell\Catalog\Events\ProductAttributeValueCreated;
use Larasell\Larasell\Catalog\Events\ProductAttributeValueDeleted;
use Larasell\Larasell\Catalog\Events\ProductAttributeValueUpdated;
use Larasell\Larasell\Catalog\Models\ProductAttribute;
use Larasell\Larasell\Catalog\Models\ProductAttributeValue;

/**
 * Audits the core's attribute events, in the core action's transaction, so a change that can't be
 * audited rolls back.
 *
 * @internal Registered by ProductServiceProvider.
 */
class AuditAttributeChanges
{
    private const string UNKNOWN = 'The attribute was changed outside a request, by a job or command.';

    public function __construct(private readonly Auditor $auditor, private readonly Origins $origins) {}

    public function created(ProductAttributeCreated $event): void
    {
        // Read back, so the record says what was stored.
        $attribute = ProductAttribute::query()->findOrFail($event->attributeId->value);

        $this->auditor->record(new AttributeCreated($event, $attribute->slug, $attribute->name->all()), $this->origins->currentOr(self::UNKNOWN));
    }

    public function updated(ProductAttributeUpdated $event): void
    {
        $this->auditor->record(new AttributeUpdated($event), $this->origins->currentOr(self::UNKNOWN));
    }

    public function deleted(ProductAttributeDeleted $event): void
    {
        $this->auditor->record(new AttributeDeleted($event), $this->origins->currentOr(self::UNKNOWN));
    }

    public function valueCreated(ProductAttributeValueCreated $event): void
    {
        $value = ProductAttributeValue::query()->findOrFail($event->valueId->value);

        $this->auditor->record(new ValueCreated($event, $value->slug, $value->name->all()), $this->origins->currentOr(self::UNKNOWN));
    }

    public function valueUpdated(ProductAttributeValueUpdated $event): void
    {
        $this->auditor->record(new ValueUpdated($event), $this->origins->currentOr(self::UNKNOWN));
    }

    public function valueDeleted(ProductAttributeValueDeleted $event): void
    {
        $this->auditor->record(new ValueDeleted($event), $this->origins->currentOr(self::UNKNOWN));
    }
}
