<?php

namespace Larasell\Admin\Seo\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Larasell\Seo\Events\SeoUrlReleased as CoreEvent;

/**
 * An old or gone URL was deleted: an admin removed it, or another page claimed its path.
 *
 * @internal Recorded from the core's event. Its event name, seo.url_released, is stable.
 */
class SeoUrlReleased extends AuditRecord
{
    public function __construct(private readonly CoreEvent $event) {}

    public function event(): string
    {
        return 'seo.url_released';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::BusinessCritical;
    }

    public function subject(): Subject
    {
        return new Subject($this->event->resourceType, $this->event->resourceId);
    }

    public function properties(): array
    {
        return [
            'sales_channel_id' => $this->event->salesChannelId->value,
            'locale' => $this->event->locale,
            'path' => $this->event->path,
            'status' => $this->event->status->value,
        ];
    }

    /**
     * The core dispatches its event once committed, so the record is stored after the change.
     */
    public function requiresTransaction(): bool
    {
        return false;
    }
}
