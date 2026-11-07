<?php

namespace Larasell\Admin\Platform\ContentLocale;

use Larasell\Larasell\Enums\Locale;

/**
 * The locales admins can edit content in, such as product names and descriptions.
 *
 * @public Bind another implementation in the container to take them from elsewhere, e.g. a sales channel.
 */
interface AvailableLocales
{
    /**
     * In the order the select offers them.
     *
     * @return non-empty-list<Locale>
     */
    public function all(): array;

    /**
     * The locale admins edit in until they pick another. One of all().
     */
    public function default(): Locale;
}
