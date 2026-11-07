<?php

namespace Larasell\Admin\Category\Exceptions;

use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;
use LogicException;

/**
 * CategorySaved was dispatched outside the save's database transaction, so its listeners can't
 * roll the category back when they fail.
 */
final class CategoryNotSavedAtomically extends LogicException implements StructuredException
{
    use HasStructuredException;

    public static function outsideTransaction(): self
    {
        return self::structured(
            errorCode: 'larasell-admin.category.saved-outside-transaction',
            message: 'CategorySaved was dispatched outside a database transaction.',
            why: 'Sections rely on their sync listeners being atomic with the category: when one fails, the category must not be saved either.',
            fix: 'Dispatch CategorySaved inside the DB::transaction() that writes the category, after the category is written.',
        );
    }
}
