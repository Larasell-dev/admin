<?php

namespace Larasell\Admin\Platform\ContentLocale\Locales;

use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Settings\LocaleSettings;

/**
 * The locales the store serves, from larasell.locale.
 */
class StoreLocales implements AvailableLocales
{
    public function __construct(private readonly LocaleSettings $settings) {}

    public function all(): array
    {
        return $this->settings->supported();
    }

    public function default(): Locale
    {
        return $this->settings->default();
    }
}
