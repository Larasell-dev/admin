<?php

namespace Larasell\Admin\Facades;

use Illuminate\Support\Facades\Facade;
use Larasell\Admin\Navigation as NavigationRegistry;

/**
 * @method static \Larasell\Admin\ValueObjects\NavigationItem add(string $label, string $route, \Larasell\Admin\Enums\Icon|string $icon)
 * @method static \Larasell\Admin\ValueObjects\NavigationItem index(string $label, string $uri, class-string<\Larasell\Admin\Tables\Table> $table, \Larasell\Admin\Enums\Icon|string $icon)
 * @method static list<\Larasell\Admin\Data\ViewModel\NavigationItemData> tree()
 */
class Navigation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NavigationRegistry::class;
    }
}
