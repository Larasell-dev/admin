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

    public static function invalidName(string $name): static
    {
        return self::structured(
            errorCode: 'larasell-admin.panel.extension.invalid-name',
            message: "The extension name [{$name}] is not a plain slug.",
            why: 'The name becomes a folder in the app\'s public/vendor, which larasell:link deletes and links again, so it may only hold letters, digits, dashes and underscores.',
            fix: 'Register the extension\'s assets with AdminPanel::vite() under a name such as "acme-wishlists".',
        );
    }
}
