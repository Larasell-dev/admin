<?php

namespace Larasell\Admin\Product\Listeners;

use Larasell\Admin\Platform\Audit\Origins;
use Larasell\Admin\Product\AuditRecords\OptionDefinitionCreated as OptionCreated;
use Larasell\Admin\Product\AuditRecords\OptionDefinitionDeleted as OptionDeleted;
use Larasell\Admin\Product\AuditRecords\OptionDefinitionUpdated as OptionUpdated;
use Larasell\Admin\Product\AuditRecords\OptionDefinitionValueCreated as ValueCreated;
use Larasell\Admin\Product\AuditRecords\OptionDefinitionValueDeleted as ValueDeleted;
use Larasell\Admin\Product\AuditRecords\OptionDefinitionValueUpdated as ValueUpdated;
use Larasell\Larasell\Audit\Auditor;
use Larasell\Larasell\Catalog\Options\Events\OptionDefinitionCreated;
use Larasell\Larasell\Catalog\Options\Events\OptionDefinitionDeleted;
use Larasell\Larasell\Catalog\Options\Events\OptionDefinitionUpdated;
use Larasell\Larasell\Catalog\Options\Events\OptionDefinitionValueCreated;
use Larasell\Larasell\Catalog\Options\Events\OptionDefinitionValueDeleted;
use Larasell\Larasell\Catalog\Options\Events\OptionDefinitionValueUpdated;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinition;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinitionValue;

/**
 * Audits the core's option events, in the core action's transaction, so a change that can't be
 * audited rolls back.
 *
 * @internal Registered by ProductServiceProvider.
 */
class AuditOptionChanges
{
    private const string UNKNOWN = 'The option was changed outside a request, by a job or command.';

    public function __construct(private readonly Auditor $auditor, private readonly Origins $origins) {}

    public function created(OptionDefinitionCreated $event): void
    {
        // Read back, so the record says what was stored.
        $option = OptionDefinition::query()->findOrFail($event->definitionId->value);

        $this->auditor->record(new OptionCreated($event, $option->slug, $option->name->all()), $this->origins->currentOr(self::UNKNOWN));
    }

    public function updated(OptionDefinitionUpdated $event): void
    {
        $this->auditor->record(new OptionUpdated($event), $this->origins->currentOr(self::UNKNOWN));
    }

    public function deleted(OptionDefinitionDeleted $event): void
    {
        $this->auditor->record(new OptionDeleted($event), $this->origins->currentOr(self::UNKNOWN));
    }

    public function valueCreated(OptionDefinitionValueCreated $event): void
    {
        $value = OptionDefinitionValue::query()->findOrFail($event->valueId->value);

        $this->auditor->record(new ValueCreated($event, $value->slug, $value->name->all()), $this->origins->currentOr(self::UNKNOWN));
    }

    public function valueUpdated(OptionDefinitionValueUpdated $event): void
    {
        $this->auditor->record(new ValueUpdated($event), $this->origins->currentOr(self::UNKNOWN));
    }

    public function valueDeleted(OptionDefinitionValueDeleted $event): void
    {
        $this->auditor->record(new ValueDeleted($event), $this->origins->currentOr(self::UNKNOWN));
    }
}
