<?php

use Illuminate\Auth\AuthenticationException;
use Larasell\Admin\Platform\Navigation\Exceptions\InvalidNavigationItem;

function plain(): void
{
    throw new LogicException('Plain.');
}

function wrapped(Throwable $previous): RuntimeException
{
    return new RuntimeException('Wrapped.', previous: $previous);
}

function structured(): void
{
    throw InvalidNavigationItem::emptyLabel();
}

function notAnException(): ArrayObject
{
    return new ArrayObject;
}

function handledByLaravel(): void
{
    throw new AuthenticationException;
}
