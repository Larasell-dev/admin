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

    public static function alreadyPlaced(string $id): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.already-placed',
            message: "Navigation item [{$id}] is already placed.",
            why: "The navigation item [{$id}] received more than one of position(), before() or after().",
            fix: 'Call only one of position(), before() or after() per navigation item.',
        );
    }

    public static function positionOutOfRange(string $id, int $position, int $min, int $max): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.position-out-of-range',
            message: "Navigation item [{$id}] position [{$position}] must be between {$min} and {$max}.",
            why: "The position [{$position}] is outside the supported range.",
            fix: "Pass a position between {$min} and {$max}.",
        );
    }

    public static function selfTarget(string $id): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.self-target',
            message: "Navigation item [{$id}] cannot be placed relative to itself.",
            why: "The navigation item [{$id}] passed its own id to before() or after().",
            fix: 'Pass the id of a different navigation item.',
        );
    }

    public static function duplicateId(string $id): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.duplicate-id',
            message: "Navigation item [{$id}] is already registered.",
            why: "Two navigation items use the route [{$id}], so before() and after() could not tell them apart.",
            fix: 'Register each route in the navigation only once.',
        );
    }

    public static function unknownTarget(string $id, string $target): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.unknown-target',
            message: "Navigation item [{$id}] targets unknown item [{$target}].",
            why: "No navigation item with the id [{$target}] is registered.",
            fix: 'Pass the id of a registered navigation item or a NavItem enum case.',
        );
    }

    /**
     * @param  list<string>  $ids
     */
    public static function placementCycle(array $ids): static
    {
        $list = implode(', ', $ids);

        return static::structured(
            code: 'larasell-admin.navigation.placement-cycle',
            message: "Navigation items [{$list}] form a placement cycle.",
            why: 'These items are placed before or after each other, so no order satisfies all of them.',
            fix: 'Anchor at least one of these items to an item outside the cycle.',
        );
    }
}
