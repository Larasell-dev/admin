<?php

namespace Larasell\Admin\Platform\Audit\Export\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Where an export of the audit log stands.
 *
 * @internal
 */
#[TypeScript]
enum AuditLogExportStatus: string
{
    /** Asked for, and waiting for or being written by the ExportAuditLog job. */
    case Pending = 'pending';

    /** Written, and can be downloaded until it expires. */
    case Ready = 'ready';

    /** Couldn't be written, or didn't finish in time. */
    case Failed = 'failed';
}
