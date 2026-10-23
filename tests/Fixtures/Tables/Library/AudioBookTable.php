<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Tables\Library;

use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;

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
