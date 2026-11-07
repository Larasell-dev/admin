<?php

namespace Larasell\Admin\Platform\Panel\Exceptions;

use InvalidArgumentException;
use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;

final class InvalidExtension extends InvalidArgumentException implements StructuredException
{
    use HasStructuredException;

    public static function notRegistered(string $name): static
    {
        return self::structured(
            errorCode: 'larasell-admin.panel.extension.not-registered',
            message: "No extension assets are registered as [{$name}].",
            why: "The panel only knows the build of extensions registered with AdminPanel::extension(), and none is named [{$name}].",
            fix: "Register the extension's assets with AdminPanel::extension() in a service provider, or pass the name it was registered with.",
        );
    }
}
