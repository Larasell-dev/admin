<?php

namespace Larasell\Admin\Platform\Audit\Export\Exceptions;

use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;
use RuntimeException;

/**
 * An export of the audit log couldn't be written to its file.
 *
 * @public Catch it; only the export creates it, so its named constructors are internal.
 */
class AuditLogExportUnwritable extends RuntimeException implements StructuredException
{
    use HasStructuredException;

    /**
     * @internal
     */
    public static function cannotOpen(string $path): static
    {
        return static::structured(
            errorCode: 'larasell-admin.audit.export-unwritable',
            message: "The audit log export couldn't open [{$path}] for writing.",
            why: "The directory doesn't exist or the app may not write to it.",
            fix: 'Pass a path in a directory the app can write to.',
        );
    }

    /**
     * @internal
     */
    public static function cannotZip(string $path): static
    {
        return static::structured(
            errorCode: 'larasell-admin.audit.export-unwritable',
            message: "The audit log export couldn't write the zip archive [{$path}].",
            why: "The directory doesn't exist or the app may not write to it.",
            fix: 'Pass a path in a directory the app can write to.',
        );
    }
}
