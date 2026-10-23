<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Tables;

use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;

/**
 * Lists Models\Book without naming it.
 */
class Book extends Table
{
    public function columns(): array
    {
        return [TextColumn::make('title')];
    }
}
