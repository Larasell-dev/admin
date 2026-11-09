<?php

namespace Larasell\Admin\SalesChannel\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Larasell\SalesChannels\Events\SalesChannelDeleted as CoreEvent;

/**
 * A sales channel was deleted, with its key and type.
 *
 * @internal Recorded from the core's event. Its event name, sales_channel.deleted, is stable.
 */
class SalesChannelDeleted extends AuditRecord
{
    public function __construct(private readonly CoreEvent $event) {}

    public function event(): string
    {
        return 'sales_channel.deleted';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::BusinessCritical;
    }

    public function subject(): Subject
    {
        return new Subject('sales_channel', $this->event->channelId->value);
    }

    public function properties(): array
    {
        return ['key' => $this->event->key, 'type' => $this->event->type];
    }
}
