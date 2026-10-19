<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures;

/**
 * A model with a multi-word class name, stored in the books table.
 */
class AudioBook extends Book
{
    protected $table = 'books';
}
