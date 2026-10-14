<?php

namespace Larasell\Admin\Navigation;

class NavigationItem
{
    public function __construct(
        public string $label,
        public string $route,
    ) {}

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
