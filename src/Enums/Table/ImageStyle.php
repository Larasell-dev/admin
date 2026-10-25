<?php

namespace Larasell\Admin\Enums\Table;

/**
 * The shape of an image in a table cell.
 */
enum ImageStyle: string
{
    /** Fully rounded, e.g. for people's avatars. */
    case Round = 'round';

    /** Square with slightly rounded corners, e.g. for covers, logos and icons. */
    case Square = 'square';
}
