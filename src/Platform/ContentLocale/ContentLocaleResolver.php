<?php

namespace Larasell\Admin\Platform\ContentLocale;

use Illuminate\Http\Request;
use Larasell\Larasell\Enums\Locale;

/**
 * Which locale a request edits content in, which is not the locale of the panel's interface.
 *
 * @public Bind another implementation in the container to resolve it differently, e.g. per sales channel.
 */
interface ContentLocaleResolver
{
    /**
     * The locale the admin picked, or a default one while they picked none or one that is no
     * longer available.
     */
    public function resolve(Request $request): Locale;

    /**
     * Keeps the admin's pick for their next requests. The locale is one of AvailableLocales::all().
     */
    public function remember(Request $request, Locale $locale): void;
}
