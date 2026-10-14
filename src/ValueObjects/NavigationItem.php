<?php

namespace Larasell\Admin\ValueObjects;

use Larasell\Admin\Exceptions\InvalidNavigationItem;

class NavigationItem
{
    public function __construct(
        public string $label,
        public string $route,
    ) {
        if (trim($label) === '') {
            throw InvalidNavigationItem::emptyLabel();
        }

        if (trim($route) === '') {
            throw InvalidNavigationItem::emptyRoute($label);
        }
    }

    /**
     * @return array{label: string, url: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'url' => route($this->route),
        ];
    }
}
