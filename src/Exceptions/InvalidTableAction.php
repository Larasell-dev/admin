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

    public static function emptyLabel(): static
    {
        return static::structured(
            code: 'larasell-admin.table.action.empty-label',
            message: 'A table action needs a label.',
            why: 'TableAction::make() was given an empty label, so its button would have no text.',
            fix: 'Pass the text the button shows, e.g. TableAction::make(\'Add product\').',
        );
    }

    public static function emptyUrl(string $action): static
    {
        return static::structured(
            code: 'larasell-admin.table.action.empty-url',
            message: "Action [{$action}] link cannot be empty.",
            why: "An empty link points back at the current page, so [{$action}] would do nothing.",
            fix: 'Pass the url the action opens to to(), or a route name to route().',
        );
    }

    public static function missingLink(string $table, string $action): static
    {
        return static::structured(
            code: 'larasell-admin.table.action.missing-link',
            message: "Table [{$table}] action [{$action}] needs a link.",
            why: "Table actions only link for now, and [{$action}] was never given a url.",
            fix: 'Call to() or route() on the action.',
        );
    }

    public static function invalidList(string $table, string $source, string $expected): static
    {
        return static::structured(
            code: 'larasell-admin.table.action.invalid-list',
            message: "Table [{$table}] {$source} must be {$expected}.",
            why: 'Anything but a TableAction, such as a row Action, is sent to the page as an empty object and shows as a button without a label or link.',
            fix: "Return a list of TableAction from {$source}, e.g. [TableAction::make('Add product')->route('products.create')].",
        );
    }

    public static function duplicateLabel(string $table, string $source, string $label): static
    {
        return static::structured(
            code: 'larasell-admin.table.action.duplicate-label',
            message: "Table [{$table}] {$source} has more than one action labelled [{$label}].",
            why: 'Two buttons with the same text cannot be told apart, and the page tells actions apart by their label.',
            fix: "Remove one of the [{$label}] actions, or give them different labels.",
        );
    }
}
