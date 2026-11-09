<?php

namespace Larasell\Admin\Platform\Auth\Exceptions;

use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;
use UnexpectedValueException;

final class InvalidAvatarPath extends UnexpectedValueException implements StructuredException
{
    use HasStructuredException;

    public static function outsideAvatars(string $path): static
    {
        return self::structured(
            errorCode: 'larasell-admin.admin.avatar-path-outside-avatars',
            message: "The avatar path [{$path}] is not a file in avatars/.",
            why: 'Avatars are uploaded into avatars/ on the panel disk, and the panel only deletes avatars there, so it never deletes another file on the disk.',
            fix: 'Set the admin\'s avatar_path only to a path returned by the avatar upload, or to null.',
        );
    }
}
