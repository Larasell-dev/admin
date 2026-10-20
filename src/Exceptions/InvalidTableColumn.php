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
}
