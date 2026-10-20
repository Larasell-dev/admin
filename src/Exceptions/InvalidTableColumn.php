<?php

namespace Larasell\Admin\Exceptions;

use InvalidArgumentException;
use Larasell\Chronicle\HasStructuredError;
use Larasell\Chronicle\StructuredError;

class InvalidTableColumn extends InvalidArgumentException implements StructuredError
{
    use HasStructuredError;

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
}
