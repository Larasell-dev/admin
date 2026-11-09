<?php

namespace Larasell\Admin\Product\Listeners;

use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Admin\Product\AuditRecords\ProductCreated as Created;
use Larasell\Admin\Product\AuditRecords\ProductDeleted as Deleted;
use Larasell\Admin\Product\AuditRecords\ProductUpdated as Updated;
use Larasell\Larasell\Catalog\Events\ProductCreated;
use Larasell\Larasell\Catalog\Events\ProductDeleted;
use Larasell\Larasell\Catalog\Events\ProductUpdated;
use Larasell\Larasell\Catalog\Models\Product;

/**
 * Audits the core's product events, in the core action's transaction, so a change that can't be
 * audited rolls back.
 *
 * @internal Registered by ProductServiceProvider.
 */
class AuditProductChanges
{
    private const string UNKNOWN = 'The product was changed outside a request, by a job or command.';

    public function __construct(private readonly Auditor $auditor) {}

    public function created(ProductCreated $event): void
    {
        // Read back, so the record says what was stored.
        $name = Product::query()->findOrFail($event->productId->value)->name;

        $this->auditor->recordForEvent(new Created(new Subject('product', $event->productId->value), ['name' => $name->all()]), self::UNKNOWN);
    }

    public function updated(ProductUpdated $event): void
    {
        if ($event->changed === []) {
            return;
        }

        $this->auditor->recordForEvent(new Updated(new Subject('product', $event->productId->value), ['changed' => $event->changed]), self::UNKNOWN);
    }

    public function deleted(ProductDeleted $event): void
    {
        $this->auditor->recordForEvent(new Deleted(new Subject('product', $event->productId->value), ['name' => $event->name->all()]), self::UNKNOWN);
    }
}
