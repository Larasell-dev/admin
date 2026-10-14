<?php

namespace Larasell\Admin\ValueObjects;

use InvalidArgumentException;

class NavigationItem
{
    public function __construct(
        public string $label,
        public string $route,
    ) {
        if (trim($label) === '') {
            throw new InvalidArgumentException('Navigation item label cannot be empty.');
        }

        if (trim($route) === '') {
            throw new InvalidArgumentException("Navigation item [{$label}] route cannot be empty.");
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