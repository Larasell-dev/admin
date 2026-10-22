<?php

namespace Larasell\Admin\Tables\Attributes;

use Attribute;

/**
 * Marks a table constructor argument to keep for the table's actions. Actions run in a
 * request of their own, which makes the table again from the remembered arguments.
 * Only use literal values and Eloquent models.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class Remember
{
    //
}
