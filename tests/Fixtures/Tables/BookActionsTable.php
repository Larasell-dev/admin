<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Tables;

use Closure;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Admin\Tests\Fixtures\Book;

/**
 * Lets each test define its own actions without declaring a table class per test.
 */
class BookActionsTable extends Table
{
    /** @var Closure(): array<int, Action> */
    public static Closure $actions;

    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }

    public function actions(): array
    {
        return (static::$actions)();
    }
}
