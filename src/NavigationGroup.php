<?php

namespace Larasell\Admin;

use Larasell\Admin\Enums\Icon;
use Larasell\Admin\ValueObjects\NavigationItem;

/**
 * The children of one navigation item, as passed to NavigationItem::children().
 */
class NavigationGroup
{
    public function __construct(
        protected AdminPanel $panel,
        protected string $parent,
    ) {}

    /**
     * Registers an item nested under this group's parent; same arguments as AdminPanel::add().
     */
    public function add(string $label, string $route, Icon|string $icon): NavigationItem
    {
        return $this->panel->add($label, $route, $icon)->under($this->parent);
    }
}
