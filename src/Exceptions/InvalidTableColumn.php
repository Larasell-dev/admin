<?php

namespace Larasell\Admin\Exceptions;

use InvalidArgumentException;
use Larasell\Chronicle\HasStructuredError;
use Larasell\Chronicle\StructuredError;

class InvalidTableColumn extends InvalidArgumentException implements StructuredError
{
    use HasStructuredError;

    public static function duplicateName(string $table, string $column): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.duplicate-name',
            message: "Table [{$table}] defines more than one column named [{$column}].",
            why: "Each row stores a column's value under its name, so the last column named [{$column}] would overwrite the others.",
            fix: "Give each column a unique name. To show [{$column}] twice, name the second column differently and read the attribute in map(), e.g. ->map(fn (\$value, \$record) => \$record->{$column}).",
        );
    }

    public static function reservedName(string $table, string $column): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.reserved-name',
            message: "Table [{$table}] defines a column named [{$column}], which is reserved.",
            why: "Every row uses [{$column}] for its own data, so a column by that name would overwrite it, or be overwritten.",
            fix: "Rename the column and compute its value with map() if it needs the attribute [{$column}].",
        );
    }

    public static function configuredTwice(string $column, string $method): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.configured-twice',
            message: "Column [{$column}] calls {$method} more than once.",
            why: "A second call to {$method} would silently replace the first, so only one of them would take effect.",
            fix: "Call {$method} once on the column, and combine both callbacks into one if you need both.",
        );
    }

    /**
     * @param  array<int, string>  $attributes
     */
    public static function recordModified(string $column, array $attributes): static
    {
        $changed = implode(', ', $attributes);

        return static::structured(
            code: 'larasell-admin.table.column.record-modified',
            message: "Column [{$column}] map() changed the record's [{$changed}].",
            why: "The map() callback of [{$column}] assigned attributes on the record, which the row's other columns, actions and link then see.",
            fix: 'Only read from the record in map() and return the value to show, without assigning attributes.',
        );
    }

    public static function invalidMappedValue(string $column, string $type): static
    {
        return static::structured(
            code: 'larasell-admin.table.column.invalid-mapped-value',
            message: "Text column [{$column}] map() returned {$type}, not text.",
            why: "A text column shows its value as text, but the map() callback of [{$column}] returned {$type}, which has no clear text form.",
            fix: 'Return a string, a number, a Stringable or null from map(), e.g. $value ? \'Yes\' : \'No\' for a bool.',
        );
    }
}
