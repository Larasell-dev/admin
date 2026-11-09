<?php

namespace Larasell\Admin\Platform\Audit\Export\Exceptions;

use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;
use RuntimeException;

/**
 * An export of the audit log is in a state the panel never puts it in.
 *
 * @public Catch it; only the export creates it, so its named constructors are internal.
 */
class InvalidAuditLogExport extends RuntimeException implements StructuredException
{
    use HasStructuredException;

    /**
     * @internal
     */
    public static function readyWithoutFile(int $id): static
    {
        return static::structured(
            errorCode: 'larasell-admin.audit.export-ready-without-file',
            message: "Audit log export [{$id}] is ready, but has no file or record count.",
            why: 'The ExportAuditLog job sets the file and count when it marks an export ready, so the row was changed elsewhere.',
            fix: 'Delete the export and ask for a new one.',
        );
    }

    /**
     * @internal
     */
    public static function notPending(int $id, string $status): static
    {
        return static::structured(
            errorCode: 'larasell-admin.audit.export-not-pending',
            message: "Audit log export [{$id}] is {$status}, so it can't fail anymore.",
            why: 'Only an export still being written can fail. Marking a finished one failed would record a failure that never happened.',
            fix: 'Check the export is pending before marking it failed.',
        );
    }

    /**
     * @internal
     */
    public static function outsideFolder(int $id, string $path): static
    {
        return static::structured(
            errorCode: 'larasell-admin.audit.export-outside-folder',
            message: "Audit log export [{$id}] points at [{$path}], outside larasell/audit-log-exports.",
            why: 'Deleting an export deletes its file, so a path elsewhere would delete a file the export never wrote.',
            fix: 'Correct or clear the path of the export in larasell_audit_log_exports, then delete it again.',
        );
    }
}
