<?php

namespace Larasell\Admin\Platform\InterfaceLocale;

/**
 * Marks text that is translated when it is shown rather than now, e.g. a navigation label
 * registered while the app boots, before a request picked its language. The navigation and the
 * searches translate their labels with __() when they show them.
 */
final class Translate
{
    /**
     * Returns the text as it is; `npm run translations:extract` collects it for the translators.
     */
    public static function later(string $text): string
    {
        return $text;
    }
}
