<?php

namespace Larasell\Admin\Facades;

use Illuminate\Support\Facades\Facade;
use Larasell\Admin\AdminPanel;

/**
 * @method static \Larasell\Admin\ValueObjects\NavigationItem add(string $label, string $route, \Larasell\Admin\Enums\NavIcon|string $icon)
 */
class Navigation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AdminPanel::class;
    }
}
