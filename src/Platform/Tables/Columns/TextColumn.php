<?php

namespace Larasell\Admin\Platform\Tables\Columns;

use Larasell\Admin\Platform\Tables\Columns\Concerns\HasTextValue;

class TextColumn extends Column
{
    use HasTextValue;

    public function type(): string
    {
        return 'text';
    }
}
