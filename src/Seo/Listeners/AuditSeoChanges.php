<?php

namespace Larasell\Admin\Seo\Listeners;

use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Admin\Seo\AuditRecords\SeoUrlReleased as Released;
use Larasell\Larasell\Seo\Events\SeoUrlReleased;

/**
 * Audits URLs being released. The core dispatches it once committed, so the record is stored right
 * after the change, and can't roll it back.
 *
 * @internal Registered by SeoServiceProvider.
 */
class AuditSeoChanges
{
    public function __construct(private readonly Auditor $auditor) {}

    public function released(SeoUrlReleased $event): void
    {
        $this->auditor->recordForEvent(new Released(new Subject($event->resourceType, $event->resourceId), [
            'sales_channel_id' => $event->salesChannelId->value,
            'locale' => $event->locale,
            'path' => $event->path,
            'status' => $event->status->value,
        ]), 'The URL was released outside a request, by a job or command.');
    }
}
