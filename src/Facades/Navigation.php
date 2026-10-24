<?php

namespace Larasell\Admin\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Larasell\Admin\ValueObjects\NavigationItem add(string $label, string $route, \Larasell\Admin\Enums\Icon|string $icon)
 * @method static list<\Larasell\Admin\Data\ViewModel\NavigationItemData> tree()
 */
class Navigation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Larasell\Admin\Navigation::class;
    }
}
