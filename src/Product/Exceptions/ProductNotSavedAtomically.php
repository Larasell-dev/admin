<?php

namespace Larasell\Admin\Product\Exceptions;

use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;
use LogicException;

/**
 * ProductSaved was dispatched outside the save's database transaction, so its listeners can't
 * roll the product back when they fail.
 */
final class ProductNotSavedAtomically extends LogicException implements StructuredException
{
    use HasStructuredException;

    public static function outsideTransaction(): self
    {
        return self::structured(
            errorCode: 'larasell-admin.product.saved-outside-transaction',
            message: 'ProductSaved was dispatched outside a database transaction.',
            why: 'Sections rely on their sync listeners being atomic with the product: when one fails, the product must not be saved either.',
            fix: 'Dispatch ProductSaved inside the DB::transaction() that writes the product, after the product is written.',
        );
    }
}
