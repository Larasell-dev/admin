<?php

namespace Larasell\Admin\SalesChannel\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;

/**
 * An entry point of a sales channel was deleted.
 *
 * @internal Recorded from the core's event. Its event name, sales_channel.entry_point_deleted, is stable.
 */
class EntryPointDeleted extends AuditRecord
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function __construct(private readonly Subject $subject, private readonly array $properties) {}

    public function event(): string
    {
        return 'sales_channel.entry_point_deleted';
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
