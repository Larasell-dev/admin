<?php

namespace Larasell\Admin\Enums;

use Larasell\Admin\Enums\Concerns\HasLabel;

enum NavItem: string
{
    use HasLabel;

    case Home = 'larasell.admin.home';
    case Products = 'larasell.admin.products.index';
    case Categories = 'larasell.admin.categories.index';
    case Customers = 'larasell.admin.customers.index';
    case Settings = 'larasell.admin.settings';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Home',
            self::Products => 'Products',
            self::Categories => 'Categories',
            self::Customers => 'Customers',
            self::Settings => 'Settings',
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
            self::Customers => 4000,
            self::Settings => 5000,
        };
    }
}
