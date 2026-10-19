<?php

namespace Larasell\Admin\Tables\Columns;

use Illuminate\Database\Eloquent\Model;

class TextColumn extends Column
{
    public function type(): string
    {
        return 'text';
    }

    public function value(Model $record): ?string
    {
        $value = parent::value($record);

        return $value === null ? null : (string) $value;
    }
}
