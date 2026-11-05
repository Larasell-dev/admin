<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Tables\Library;

use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Table;

/**
 * Lists Models\Library\AudioBook without naming it.
 */
class AudioBookTable extends Table
{
    public function columns(): array
    {
        return [TextColumn::make('title')];
    }
}
