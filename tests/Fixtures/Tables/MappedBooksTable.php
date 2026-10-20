<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Tables;

use Larasell\Admin\Tables\Attributes\UseModel;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Admin\Tests\Fixtures\Book;

/**
 * Analysed by PHPStan: a map() callback may type its record as the table's model.
 */
#[UseModel(Book::class)]
class MappedBooksTable extends Table
{
    public function columns(): array
    {
        return [
            TextColumn::make('byline')->map(fn (mixed $value, Book $book): string => "{$book->getAttribute('title')} by {$book->getAttribute('author_name')}"),
        ];
    }
}
