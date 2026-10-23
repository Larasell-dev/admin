<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Models\Library;

use Larasell\Admin\Tests\Fixtures\Book;

/**
 * A model in a nested Models namespace, for tables that infer their model from their class name.
 */
class AudioBook extends Book
{
    protected $table = 'books';
}
