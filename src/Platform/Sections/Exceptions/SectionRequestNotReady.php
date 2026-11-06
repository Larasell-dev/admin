<?php

namespace Larasell\Admin\Platform\Sections\Exceptions;

use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;
use LogicException;

/**
 * A section request was used before the page validated it, or without its HTTP request.
 */
final class SectionRequestNotReady extends LogicException implements StructuredException
{
    use HasStructuredException;

    public static function notValidated(string $request): self
    {
        return self::structured(
            errorCode: 'larasell-admin.section.request-not-validated',
            message: "The [{$request}] section has not been validated yet.",
            why: 'validated() and payload() only answer once the page has validated the section, together with its own fields.',
            fix: 'Read them in a listener of the page\'s saved event, e.g. ProductSaved, or in after(); in rules() and prepareForValidation(), use input().',
        );
    }

    public static function withoutRequest(string $request): self
    {
        return self::structured(
            errorCode: 'larasell-admin.section.request-missing',
            message: "The [{$request}] section has no request to change.",
            why: 'A section request that was serialized for a queued listener keeps its validated input, not the HTTP request merge() writes to.',
            fix: 'Change the input in prepareForValidation(); in a queued listener, use validated() or payload().',
        );
    }
}
