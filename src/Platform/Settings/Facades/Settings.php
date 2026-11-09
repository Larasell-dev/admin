<?php

namespace Larasell\Admin\Platform\Settings\Facades;

use Illuminate\Support\Facades\Facade;
use Larasell\Admin\Platform\Settings\Settings as SettingsRegistry;

/**
 * Adds pages to the settings index from a service provider's boot():
 * `Settings::add('acme-wishlists')->label($label)->icon(Icon::Heart)->route('acme.wishlists.settings')`.
 *
 * @method static \Larasell\Admin\Platform\Settings\SettingsPage add(string $id)
 * @method static list<\Larasell\Admin\Platform\Settings\Data\SettingsPageData> data()
 *
 * @see SettingsRegistry
 */
class Settings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingsRegistry::class;
    }
}
