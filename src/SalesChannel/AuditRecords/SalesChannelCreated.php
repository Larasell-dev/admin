<?php

namespace Larasell\Admin\SalesChannel\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;

/**
 * A sales channel was created, with its key and type.
 *
 * @internal Recorded from the core's event. Its event name, sales_channel.created, is stable.
 */
class SalesChannelCreated extends AuditRecord
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function __construct(private readonly Subject $subject, private readonly array $properties) {}

    public function event(): string
    {
        return 'sales_channel.created';
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
