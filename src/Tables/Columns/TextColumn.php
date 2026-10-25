<?php

namespace Larasell\Admin\Tables\Columns;

use Larasell\Admin\Tables\Columns\Concerns\HasTextValue;

class TextColumn extends Column
{
    use HasTextValue;

    public function type(): string
    {
        return 'text';
    }
}
