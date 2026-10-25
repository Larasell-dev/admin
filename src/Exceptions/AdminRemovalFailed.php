<?php

namespace Larasell\Admin\Exceptions;

use Larasell\Chronicle\HasStructuredError;
use Larasell\Chronicle\StructuredError;
use RuntimeException;

class AdminRemovalFailed extends RuntimeException implements StructuredError
{
    use HasStructuredError;

    public static function emailMismatch(string $requested, string $found): static
    {
        return static::structured(
            code: 'larasell-admin.admin.email-mismatch',
            message: "Admin lookup for [{$requested}] returned the admin [{$found}].",
            why: 'The database matched a different email, for example because its collation compares emails case-insensitively.',
            fix: 'Pass the email exactly as it is stored. Run larasell:list-admins to see it.',
        );
    }

    public static function lastAdmin(string $email): static
    {
        return static::structured(
            code: 'larasell-admin.admin.last-admin',
            message: "Admin user [{$email}] is the last admin and cannot be removed.",
            why: 'Without an admin, nobody could sign in to the admin panel.',
            fix: 'Create another admin first, for example with larasell:create-admin.',
        );
    }

    public static function notDeleted(string $email): static
    {
        return static::structured(
            code: 'larasell-admin.admin.not-deleted',
            message: "Admin user [{$email}] was not deleted.",
            why: 'Deleting the admin returned false, usually because a deleting model event listener cancelled it.',
            fix: 'Check the deleting listeners and observers registered for the Admin model.',
        );
    }

    public static function stillExists(string $email): static
    {
        return static::structured(
            code: 'larasell-admin.admin.still-exists',
            message: "Admin user [{$email}] still exists after it was deleted.",
            why: 'The delete reported success, but the row is still in the database.',
            fix: 'Check for a global scope or soft deletes on the Admin model that change how it is deleted.',
        );
    }

    public static function avatarNotDeleted(string $email, string $path): static
    {
        return static::structured(
            code: 'larasell-admin.admin.avatar-not-deleted',
            message: "Avatar [{$path}] of the removed admin [{$email}] could not be deleted.",
            why: 'The admin was removed, but the filesystem disk refused to delete the avatar file.',
            fix: 'Check the permissions of the configured larasell.admin.panel.disk and delete the file manually.',
        );
    }
}
