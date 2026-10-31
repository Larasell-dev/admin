<?php

namespace Larasell\Admin\Audit;

/**
 * Groups records by why they're kept, e.g. to keep each group for its own retention period.
 *
 * @public
 */
enum AuditCategory: string
{
    case Security = 'security';
    case BusinessCritical = 'business_critical';
    case AuditAccess = 'audit_access';
}
