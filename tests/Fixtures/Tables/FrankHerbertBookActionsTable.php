<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Tables;

use Illuminate\Database\Eloquent\Builder;
use Larasell\Admin\Tests\Fixtures\Book;

/**
 * Runs the same actions as BookActionsTable, but only lists Frank Herbert's books.
 */
class FrankHerbertBookActionsTable extends BookActionsTable
{
    /**
     * @return Builder<Book>
     */
    protected function model(): Builder
    {
        return Book::where('author_name', 'Frank Herbert');
    }
}
