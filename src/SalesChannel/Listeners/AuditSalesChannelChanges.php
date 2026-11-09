<?php

namespace Larasell\Admin\SalesChannel\Listeners;

use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Admin\SalesChannel\AuditRecords\EntryPointAdded;
use Larasell\Admin\SalesChannel\AuditRecords\EntryPointDeleted as EntryPointDeletedRecord;
use Larasell\Admin\SalesChannel\AuditRecords\SalesChannelCreated as Created;
use Larasell\Admin\SalesChannel\AuditRecords\SalesChannelDeleted as Deleted;
use Larasell\Admin\SalesChannel\AuditRecords\SalesChannelUpdated as Updated;
use Larasell\Larasell\SalesChannels\Events\SalesChannelCreated;
use Larasell\Larasell\SalesChannels\Events\SalesChannelDeleted;
use Larasell\Larasell\SalesChannels\Events\SalesChannelUpdated;
use Larasell\Larasell\Storefront\Events\EntryPointCreated;
use Larasell\Larasell\Storefront\Events\EntryPointDeleted;

/**
 * Audits the core's sales channel and entry point events, in the core action's transaction.
 *
 * @internal Registered by SalesChannelServiceProvider.
 */
class AuditSalesChannelChanges
{
    private const string UNKNOWN = 'The sales channel was changed outside a request, by a job or command.';

    public function __construct(private readonly Auditor $auditor) {}

    public function created(SalesChannelCreated $event): void
    {
        $this->auditor->recordForEvent(new Created($this->subject($event->channelId->value), ['key' => $event->key, 'type' => $event->type]), self::UNKNOWN);
    }

    public function updated(SalesChannelUpdated $event): void
    {
        $this->auditor->recordForEvent(new Updated($this->subject($event->channelId->value), ['changed' => $event->changed]), self::UNKNOWN);
    }

    public function deleted(SalesChannelDeleted $event): void
    {
        $this->auditor->recordForEvent(new Deleted($this->subject($event->channelId->value), ['key' => $event->key, 'type' => $event->type]), self::UNKNOWN);
    }

    public function entryPointCreated(EntryPointCreated $event): void
    {
        $this->auditor->recordForEvent(new EntryPointAdded($this->subject($event->channelId->value), [
            'entry_point_id' => $event->entryPointId->value,
            'locale' => $event->locale->value,
            'default_currency' => $event->defaultCurrency?->value,
        ]), self::UNKNOWN);
    }

    public function entryPointDeleted(EntryPointDeleted $event): void
    {
        $this->auditor->recordForEvent(new EntryPointDeletedRecord($this->subject($event->channelId->value), ['entry_point_id' => $event->entryPointId->value]), self::UNKNOWN);
    }

    private function subject(int $id): Subject
    {
        return new Subject('sales_channel', $id);
    }
}
