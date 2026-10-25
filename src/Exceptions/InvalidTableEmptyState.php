<?php

namespace Larasell\Admin\Exceptions;

use InvalidArgumentException;
use Larasell\Chronicle\HasStructuredError;
use Larasell\Chronicle\StructuredError;

class InvalidTableEmptyState extends InvalidArgumentException implements StructuredError
{
    use HasStructuredError;

    public static function missingTitle(string $table): static
    {
        return static::structured(
            code: 'larasell-admin.table.empty-state.missing-title',
            message: "Table [{$table}] empty state needs a title.",
            why: "The empty state of [{$table}] has no title, or a blank one, so there is nothing to show when the table has no rows.",
            fix: "Return a non-empty 'title' from emptyState(), e.g. ['title' => 'No orders yet'].",
        );
    }

    public static function invalidValue(string $table, string $key, string $expected): static
    {
        return static::structured(
            code: 'larasell-admin.table.empty-state.invalid-value',
            message: "Table [{$table}] empty state [{$key}] must be {$expected}.",
            why: "The empty state of [{$table}] has a [{$key}] value of the wrong type.",
            fix: "Return {$expected} for [{$key}] from emptyState().",
        );
    }

    public static function unknownKey(string $table, string $key): static
    {
        return static::structured(
            code: 'larasell-admin.table.empty-state.unknown-key',
            message: "Table [{$table}] empty state has an unknown key [{$key}].",
            why: "An empty state only supports 'title', 'description', 'icon' and 'actions', so [{$key}] would be ignored.",
            fix: "Remove [{$key}] from emptyState(), or check it for a typo.",
        );
    }

    public static function emptyIcon(string $table): static
    {
        return static::structured(
            code: 'larasell-admin.table.empty-state.empty-icon',
            message: "Table [{$table}] empty state icon cannot be empty.",
            why: "The empty state of [{$table}] has an empty icon name, so there is no icon to show.",
            fix: "Return an Icon case or a Remix icon name for 'icon', e.g. Icon::Book or \"book\", or leave 'icon' out for the default.",
        );
    }

    public static function iconStyleVariant(string $table, string $icon): static
    {
        return static::structured(
            code: 'larasell-admin.table.empty-state.icon-style-variant',
            message: "Table [{$table}] empty state icon must not end in -line or -fill.",
            why: "The icon [{$icon}] names one style, but the empty state picks the style itself.",
            fix: 'Pass the icon name without its -line or -fill suffix, e.g. "book" instead of "book-line".',
        );
    }

    public static function unknownIcon(string $table, string $icon): static
    {
        return static::structured(
            code: 'larasell-admin.table.empty-state.unknown-icon',
            message: "Table [{$table}] empty state icon [{$icon}] is not a Remix icon.",
            why: "No Icon case has the value [{$icon}], so the empty state cannot render it.",
            fix: 'Pass an Icon case, or a lowercase Remix icon name from https://remixicon.com such as "file-list-3".',
        );
    }
}
