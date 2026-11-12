<?php

namespace Larasell\Admin\Platform\Audit\Exceptions;

use Larasell\Chronicle\Exceptions\HasStructuredException;
use Larasell\Chronicle\Exceptions\StructuredException;
use LogicException;

/** A change has to be audited, but nothing says who made it. */
final class MissingOrigin extends LogicException implements StructuredException
{
    use HasStructuredException;

    public static function noOneSignedIn(): self
    {
        return self::structured(
            errorCode: 'larasell-admin.audit.no-origin',
            message: 'No admin or API token is signed in, so the request has no origin to audit its changes with.',
            why: 'Every audited change names who made it; a request with no one signed in can\'t say.',
            fix: 'Put the route behind the panel\'s or the API\'s authentication, or pass an Origin yourself, e.g. Origin::cli(new SystemActor(\'...\')).',
        );
    }
}
