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
}
