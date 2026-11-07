<?php

namespace Larasell\Admin\Platform\InterfaceLocale\Locales;

use Larasell\Admin\Platform\InterfaceLocale\InterfaceLocale;

/**
 * The languages the panel ships translations for, and the pseudo-languages while it runs locally,
 * the only place they are of use.
 */
class PanelInterfaceLocales implements AvailableInterfaceLocales
{
    /**
     * Each but English has a file in resources/translations, for the frontend, and in lang, for the
     * server. `npm run translations:extract -- <code>` starts both.
     */
    public const array TRANSLATED = [InterfaceLocale::SOURCE, 'de-DE'];

    public function all(): array
    {
        $codes = [...self::TRANSLATED, ...(app()->isLocal() ? InterfaceLocale::PSEUDO : [])];

        return array_map(fn (string $code): InterfaceLocale => new InterfaceLocale($code), $codes);
    }

    public function default(): InterfaceLocale
    {
        return new InterfaceLocale(InterfaceLocale::SOURCE);
    }
}
