<?php

namespace Larasell\Admin\Platform\Navigation;

use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Support\Concerns\HasLabel;

enum NavItem: string
{
    use HasLabel;

    case Home = 'larasell.admin.home';
    case Products = 'larasell.admin.products.index';
    case Categories = 'larasell.admin.categories.index';
    case Orders = 'larasell.admin.orders.index';
    case Settings = 'larasell.admin.settings';

    public function label(): string
    {
        return match ($this) {
            self::Home => Translate::later('Home'),
            self::Products => Translate::later('Products'),
            self::Categories => Translate::later('Categories'),
            self::Orders => Translate::later('Orders'),
            self::Settings => Translate::later('Settings'),
        };
    }

    /**
     * Built-in items sit 1000 apart, so a custom item can take any position between two of them.
     */
    public function position(): int
    {
        return match ($this) {
            self::Home => 1000,
            self::Products => 2000,
            self::Categories => 3000,
            self::Orders => 4000,
            self::Settings => 5000,
        };
    }
}
