<?php

namespace Larasell\Admin\Facades;

use Illuminate\Support\Facades\Facade;
use Larasell\Admin\AdminPanel;

/**
 * @method static void add(string $label, string $route)
 */
class Navigation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AdminPanel::class;
    }
}
