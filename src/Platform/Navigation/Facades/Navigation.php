<?php

namespace Larasell\Admin\Platform\Navigation\Facades;

use Illuminate\Support\Facades\Facade;
use Larasell\Admin\Platform\Navigation\Navigation as NavigationRegistry;

/**
 * @method static \Larasell\Admin\Platform\Navigation\NavigationItem add(string $label, string $route, \Larasell\Admin\Platform\Panel\Icon|string $icon)
 * @method static \Larasell\Admin\Platform\Navigation\NavigationItem index(string $label, string $uri, class-string<\Larasell\Admin\Platform\Tables\Table> $table, \Larasell\Admin\Platform\Panel\Icon|string $icon)
 * @method static list<\Larasell\Admin\Platform\Navigation\Data\NavigationItemData> tree()
 */
class Navigation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NavigationRegistry::class;
    }
}
