<?php

namespace Larasell\Admin\Enums;

enum NavItem: string
{
    case Home = 'larasell.admin.home';
    case Categories = 'larasell.admin.categories.index';
    case Customers = 'larasell.admin.customers.index';

    /**
     * Built-in items sit 1000 apart, so a custom item can take any position between two of them.
     */
    public function position(): int
    {
        return match ($this) {
            self::Home => 1000,
            self::Categories => 2000,
            self::Customers => 3000,
        };
    }
}
