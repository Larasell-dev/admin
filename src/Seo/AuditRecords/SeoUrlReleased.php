<?php

namespace Larasell\Admin\Seo\AuditRecords;

use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Subject;

/**
 * An old or gone URL was deleted: an admin removed it, or another page claimed its path.
 *
 * @internal Recorded from the core's event. Its event name, seo.url_released, is stable.
 */
class SeoUrlReleased extends AuditRecord
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function __construct(private readonly Subject $subject, private readonly array $properties) {}

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
        return $this->subject;
    }

    public function properties(): array
    {
        return $this->properties;
    }

    /**
     * The core dispatches its event once committed, so the record is stored after the change.
     */
    public function requiresTransaction(): bool
    {
        return false;
    }
}
