<?php

namespace Larasell\Admin\Platform\InterfaceLocale;

/**
 * The languages admins can show the panel in.
 *
 * @public Bind another implementation in the container to offer others, e.g. with an app's own translations.
 */
interface AvailableInterfaceLocales
{
    /**
     * In the order the command menu offers them.
     *
     * @return non-empty-list<InterfaceLocale>
     */
    public function all(): array;

    /**
     * The language shown while an admin picked none, or one that is no longer available. One of all().
     */
    public function default(): InterfaceLocale;
}
