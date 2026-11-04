<?php

namespace Larasell\Admin\ContentLocale;

use Illuminate\Http\Request;
use Larasell\Larasell\Enums\Locale;

/**
 * Keeps the admin's pick in their session, so it lasts across pages until they sign out.
 */
class SessionContentLocaleResolver implements ContentLocaleResolver
{
    private const string KEY = 'larasell.admin.content_locale';

    public function __construct(private readonly AvailableLocales $locales) {}

    public function resolve(Request $request): Locale
    {
        $locale = Locale::tryFrom((string) $request->session()->get(self::KEY));

        return in_array($locale, $this->locales->all(), true) ? $locale : $this->locales->default();
    }

    public function remember(Request $request, Locale $locale): void
    {
        $request->session()->put(self::KEY, $locale->value);
    }
}
