<?php

namespace Larasell\Admin\Platform\InterfaceLocale;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Keeps the admin's pick in a cookie, so it lasts in their browser, signed in or not, like the theme.
 */
class CookieInterfaceLocaleResolver implements InterfaceLocaleResolver
{
    public const string COOKIE = 'larasell_admin_interface_locale';

    public function __construct(private readonly AvailableInterfaceLocales $locales) {}

    public function resolve(Request $request): InterfaceLocale
    {
        $code = $request->cookie(self::COOKIE);

        foreach ($this->locales->all() as $locale) {
            if ($locale->code === $code) {
                return $locale;
            }
        }

        return $this->locales->default();
    }

    public function remember(Request $request, InterfaceLocale $locale): void
    {
        Cookie::queue(Cookie::forever(self::COOKIE, $locale->code));
    }
}
