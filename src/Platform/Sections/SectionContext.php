<?php

namespace Larasell\Admin\Platform\Sections;

use Larasell\Larasell\Enums\Locale;

/**
 * What the page is shown and saved in, passed to every section instead of read from globals.
 *
 * @public
 */
final readonly class SectionContext
{
    public function __construct(
        /** The content locale the page edits, e.g. the product's name in German. */
        public Locale $locale,
    ) {}
}
