<?php

namespace Larasell\Admin\Platform\ContentLocale\Actions;

use Larasell\Larasell\Enums\Locale;

/**
 * The locale an admin edits content in from now on. One of AvailableLocales::all().
 */
final readonly class SetContentLocalePayload
{
    public function __construct(
        public Locale $locale,
    ) {}
}
