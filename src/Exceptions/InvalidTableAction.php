<?php

namespace Larasell\Admin\Exceptions;

use InvalidArgumentException;
use Larasell\Chronicle\HasStructuredError;
use Larasell\Chronicle\StructuredError;

class InvalidTableAction extends InvalidArgumentException implements StructuredError
{
    use HasStructuredError;

    public static function configuredTwice(string $action, string $method): static
    {
        return static::structured(
            code: 'larasell-admin.table.action.configured-twice',
            message: "Action [{$action}] calls {$method} more than once.",
            why: "A second call to {$method} would silently replace the first, so only one of them would take effect.",
            fix: "Call {$method} once on the action.",
        );
    }

    public static function emptyIcon(string $action): static
    {
        return static::structured(
            code: 'larasell-admin.table.action.empty-icon',
            message: "Action [{$action}] icon cannot be empty.",
            why: "The action [{$action}] was given an empty icon name, so there is no icon to show.",
            fix: 'Pass an Icon case or a Remix icon name, e.g. Icon::DeleteBin or "delete-bin", or do not call icon().',
        );
    }

    public static function iconStyleVariant(string $action, string $icon): static
    {
        return static::structured(
            code: 'larasell-admin.table.action.icon-style-variant',
            message: "Action [{$action}] icon must not end in -line or -fill.",
            why: "The icon [{$icon}] names one style, but the actions menu picks the style itself and always shows the line style.",
            fix: 'Pass the icon name without its -line or -fill suffix, e.g. "delete-bin" instead of "delete-bin-line".',
        );
    }

    public static function unknownIcon(string $action, string $icon): static
    {
        return static::structured(
            code: 'larasell-admin.table.action.unknown-icon',
            message: "Action [{$action}] icon [{$icon}] is not a Remix icon.",
            why: "No Icon case has the value [{$icon}], so the actions menu cannot render it.",
            fix: 'Pass an Icon case, or a lowercase Remix icon name from https://remixicon.com such as "delete-bin".',
        );
    }
}
