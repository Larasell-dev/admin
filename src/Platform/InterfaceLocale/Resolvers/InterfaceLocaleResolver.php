<?php

namespace Larasell\Admin\Platform\InterfaceLocale;

use Illuminate\Http\Request;

/**
 * How a request picks the language of the panel's interface, which is not the content locale.
 *
 * @public Bind another implementation in the container to pick it differently, e.g. from the admin's account.
 */
interface InterfaceLocaleResolver
{
    /**
     * The language the admin picked, or the default one while they picked none or one that is no
     * longer available. One of AvailableInterfaceLocales::all().
     */
    public function resolve(Request $request): InterfaceLocale;

    /**
     * Keeps the admin's pick for their next requests. The locale is one of AvailableInterfaceLocales::all().
     */
    public function remember(Request $request, InterfaceLocale $locale): void;
}
