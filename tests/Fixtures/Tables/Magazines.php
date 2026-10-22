<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Tables;

use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;

/**
 * Names no model, and there is no Models\Magazine to infer.
 */
class Magazines extends Table
{
    public function columns(): array
    {
        return [TextColumn::make('title')];
    }
}
