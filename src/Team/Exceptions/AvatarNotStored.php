<?php

namespace Larasell\Admin\Team\Exceptions;

use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;
use RuntimeException;

final class AvatarNotStored extends RuntimeException implements StructuredException
{
    use HasStructuredException;

    public static function onDisk(): static
    {
        $disk = (string) config('larasell.admin.panel.disk');

        return self::structured(
            errorCode: 'larasell-admin.admin.avatar-not-stored',
            message: 'Unable to store the avatar.',
            why: "The disk [{$disk}] refused to store the uploaded file.",
            fix: 'Check that the disk set in larasell.admin.panel.disk exists and is writable.',
        );
    }
}
