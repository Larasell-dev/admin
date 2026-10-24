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

    public static function emptyIcon(string $id): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.empty-icon',
            message: "Navigation item [{$id}] icon cannot be empty.",
            why: "The navigation item [{$id}] was registered without an icon, but every navigation item needs one.",
            fix: 'Pass an Icon case or a Remix icon name, e.g. Icon::Home or "home".',
        );
    }

    public static function iconStyleVariant(string $id, string $icon): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.icon-style-variant',
            message: "Navigation item [{$id}] icon must not end in -line or -fill.",
            why: "The icon [{$icon}] names one style, but the sidebar picks the style itself: filled when the item is active, line otherwise.",
            fix: 'Pass the icon name without its -line or -fill suffix, e.g. "home" instead of "home-line".',
        );
    }

    public static function unknownIcon(string $id, string $icon): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.unknown-icon',
            message: "Navigation item [{$id}] icon [{$icon}] is not a Remix icon.",
            why: "No Icon case has the value [{$icon}], so the sidebar cannot render it.",
            fix: 'Pass an Icon case, or a lowercase Remix icon name from https://remixicon.com such as "file-list-3".',
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

    public static function selfParent(string $id): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.self-parent',
            message: "Navigation item [{$id}] cannot be nested under itself.",
            why: "The navigation item [{$id}] passed its own id to under().",
            fix: 'Pass the id of a top-level navigation item.',
        );
    }

    public static function alreadyNested(string $id, string $parent): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.already-nested',
            message: "Navigation item [{$id}] is already nested under [{$parent}].",
            why: "The navigation item [{$id}] received a second parent, from under() or from being added inside children().",
            fix: 'Nest each navigation item under one parent only.',
        );
    }

    public static function unknownParent(string $id, string $parent): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.unknown-parent',
            message: "Navigation item [{$id}] is nested under unknown item [{$parent}].",
            why: "No navigation item with the id [{$parent}] is registered.",
            fix: 'Pass the id of a registered top-level navigation item or a NavItem enum case.',
        );
    }

    public static function nestedTooDeep(string $id, string $parent): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.nested-too-deep',
            message: "Navigation item [{$id}] is nested under [{$parent}], which is itself nested.",
            why: 'The navigation supports one level of nesting: top-level items and their children.',
            fix: "Nest [{$id}] under a top-level item instead of [{$parent}].",
        );
    }

    public static function targetNotSibling(string $id, string $target): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.target-not-sibling',
            message: "Navigation item [{$id}] cannot be placed relative to [{$target}], which is not in the same group.",
            why: 'before() and after() order an item among its siblings: top-level items, or the children of one parent.',
            fix: "Target an item with the same parent as [{$id}], or nest both under the same item.",
        );
    }

    public static function frozen(string $id): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.frozen',
            message: "Navigation item [{$id}] was changed after the admin panel booted.",
            why: 'The navigation is registered once at boot and shared by every request, so a change made during a request would leak into later requests.',
            fix: "Register and place navigation items in a service provider's boot() method or a routes file.",
        );
    }

    public static function unknownRoute(string $id): static
    {
        return static::structured(
            code: 'larasell-admin.navigation.unknown-route',
            message: "Navigation item [{$id}] route is not registered.",
            why: "No route is named [{$id}], so the sidebar cannot link to the item.",
            fix: 'Register the route, or pass the name of an existing route; names without a dot get the larasell.admin. prefix.',
        );
    }
}
