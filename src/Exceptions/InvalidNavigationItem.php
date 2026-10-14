<?php

namespace Larasell\Admin\Exceptions;

use InvalidArgumentException;
use Larasell\Chronicle\HasStructuredError;
use Larasell\Chronicle\StructuredError;

class InvalidNavigationItem extends InvalidArgumentException implements StructuredError
{
    use HasStructuredError;

    public static function emptyLabel(): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.empty-label',
            message: 'Navigation item label cannot be empty.',
            why: 'A navigation item without a label cannot be rendered in the admin panel.',
            fix: 'Pass a non-empty label when registering the navigation item.',
        );
    }

    public static function emptyRoute(string $label): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.empty-route',
            message: "Navigation item [{$label}] route cannot be empty.",
            why: "The navigation item [{$label}] was registered without a route name.",
            fix: 'Pass a route name when registering the navigation item.',
        );
    }
}
