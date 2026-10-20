<?php

declare(strict_types=1);

namespace Larasell\Admin\Tests\Fixtures\Tables;

use Larasell\Admin\Tests\Fixtures\AudioBook;

/**
 * BookActionsTable for a model with a multi-word class name.
 */
class AudioBookActionsTable extends BookActionsTable
{
    protected ?string $model = AudioBook::class;
}
