<?php

namespace Larasell\Admin\Enums\Table;

/**
 * The colour of a badge in a table cell, by what its text means.
 */
enum Variant: string
{
    /** No particular meaning, e.g. a draft. The default. */
    case Neutral = 'neutral';

    /** Something to know about, e.g. shipped. */
    case Info = 'info';

    /** Something that went well, e.g. paid or approved. */
    case Success = 'success';

    /** Something that needs attention, e.g. pending. */
    case Warning = 'warning';

    /** Something that went wrong, e.g. rejected or refunded. */
    case Danger = 'danger';
}
