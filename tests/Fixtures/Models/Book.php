<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Models;

use Larasell\Admin\Tests\Fixtures\Book as BaseBook;

/**
 * A model in a Models namespace, for tables that infer their model from their class name.
 */
class Book extends BaseBook
{
    protected $table = 'books';
}
