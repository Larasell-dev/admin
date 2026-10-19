<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Tables;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Admin\Tests\Fixtures\Book;

/**
 * Lets each test define its own model and actions without declaring a table class per test.
 */
class BookActionsTable extends Table
{
    /** @var Closure(): array<int, Action> */
    public static Closure $actions;

    /** @var class-string<Book> */
    public static string $bookModel = Book::class;

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }

    protected function query(): Builder
    {
        return static::$bookModel::query();
    }

    public function actions(): array
    {
        return (static::$actions)();
    }
}
