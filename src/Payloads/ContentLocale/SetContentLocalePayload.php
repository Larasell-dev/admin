<?php

namespace Larasell\Admin\Payloads\ContentLocale;

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
