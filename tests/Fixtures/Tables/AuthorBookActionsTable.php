<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Tables;

use Illuminate\Database\Eloquent\Builder;
use Larasell\Admin\Tables\Attributes\Remember;
use Larasell\Admin\Tests\Fixtures\Book;

/**
 * Runs the same actions as BookActionsTable, but only lists the books by the author it is made for.
 */
class AuthorBookActionsTable extends BookActionsTable
{
    public function __construct(
        #[Remember] public string $author,
    ) {}

    /**
     * @return Builder<Book>
     */
    protected function model(): Builder
    {
        return Book::where('author_name', $this->author);
    }
}
