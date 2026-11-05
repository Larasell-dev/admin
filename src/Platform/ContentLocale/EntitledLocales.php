<?php

namespace Larasell\Admin\Platform\ContentLocale;

use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Facades\Entitlement;

/**
 * Offers only the default locale while the plan lacks the content translations entitlement, so
 * products are read and saved in it alone and the select is hidden. The panel wraps whichever
 * AvailableLocales is bound in it, an app's or a sales channel's too.
 *
 * @internal Registered by AdminServiceProvider.
 */
final readonly class EntitledLocales implements AvailableLocales
{
    public const string ENTITLEMENT = 'admin.content-translations';

    public function __construct(private AvailableLocales $locales) {}

    public function all(): array
    {
        return Entitlement::active(self::ENTITLEMENT) ? $this->locales->all() : [$this->locales->default()];
    }

    public function default(): Locale
    {
        return $this->locales->default();
    }
}
