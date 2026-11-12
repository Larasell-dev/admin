<?php

namespace Larasell\Admin\Product\Exceptions;

use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;
use LogicException;

/** An option or one of its values would be saved without a name in any locale. */
final class OptionNameMissing extends LogicException implements StructuredException
{
    use HasStructuredException;

    public static function inEveryLocale(): self
    {
        return self::structured(
            errorCode: 'larasell-admin.product.option-name-missing',
            message: 'An option or value without a name in the content locale has no other one.',
            why: 'OptionRequest requires a name where no other locale has one, so this is a bug.',
        );
    }
}
