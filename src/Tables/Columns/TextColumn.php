<?php

namespace Larasell\Admin\Tables\Columns;

use Illuminate\Database\Eloquent\Model;
use Larasell\Admin\Exceptions\InvalidTableColumn;
use Stringable;

class TextColumn extends Column
{
    public function type(): string
    {
        return 'text';
    }

    public function value(Model $record): ?string
    {
        $value = parent::value($record);

        // A bool would silently show as "1" or "", and arrays and other objects can't be cast at all.
        if ($this->map !== null && ! ($value === null || is_string($value) || is_int($value) || is_float($value) || $value instanceof Stringable)) {
            throw InvalidTableColumn::invalidMappedValue($this->name, get_debug_type($value));
        }

        return $value === null ? null : (string) $value;
    }
}
