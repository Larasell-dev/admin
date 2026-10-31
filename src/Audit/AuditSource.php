<?php

namespace Larasell\Admin\Audit;

/**
 * How an audited action came in, apart from who did it.
 *
 * @internal The Auditor determines it; records and callers don't set it.
 */
enum AuditSource: string
{
    case Web = 'web';
    case Api = 'api';
    case Cli = 'cli';
    case Job = 'job';
}
