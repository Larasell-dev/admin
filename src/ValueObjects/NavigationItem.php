<?php

namespace Larasell\Admin\ValueObjects;

use Larasell\Admin\Enums\NavIcon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Exceptions\InvalidNavigationItem;

class NavigationItem
{
    public const POSITION_MIN = 0;

    public const POSITION_MAX = 1_000_000;

    public ?int $position = null;

    public ?string $before = null;

    public ?string $after = null;

    public NavIcon $icon;

    /**
     * @param  NavIcon|string  $icon  A NavIcon case or its Remix icon name, e.g. "home"; the sidebar renders it filled when active, line otherwise.
     */
    public function __construct(
        public string $label,
        public string $route,
        NavIcon|string $icon,
    ) {
        if (trim($label) === '') {
            throw InvalidNavigationItem::emptyLabel();
        }

        if (trim($route) === '') {
            throw InvalidNavigationItem::emptyRoute($label);
        }

        $this->icon = $icon instanceof NavIcon ? $icon : $this->resolveIcon($icon);
    }

    public function id(): string
    {
        return $this->route;
    }

    public function position(int $position): static
    {
        $this->assertUnplaced();

        if ($position < self::POSITION_MIN || $position > self::POSITION_MAX) {
            throw InvalidNavigationItem::positionOutOfRange($this->id(), $position, self::POSITION_MIN, self::POSITION_MAX);
        }

        $this->position = $position;

        return $this;
    }

    public function before(NavItem|string $target): static
    {
        $this->before = $this->resolveTarget($target);

        return $this;
    }

    public function after(NavItem|string $target): static
    {
        $this->after = $this->resolveTarget($target);

        return $this;
    }

    /**
     * The id this item is placed before or after, if it is placed relatively.
     */
    public function relativeTarget(): ?string
    {
        return $this->before ?? $this->after;
    }

    public function isPlaced(): bool
    {
        return $this->position !== null || $this->before !== null || $this->after !== null;
    }

    /**
     * The icon is sent as its case name, which @larasell-dev/ui/icons exports with a Fill or Line suffix.
     *
     * @return array{id: string, label: string, url: string, icon: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'label' => $this->label,
            'url' => route($this->route),
            'icon' => $this->icon->name,
        ];
    }

    protected function resolveIcon(string $icon): NavIcon
    {
        if (trim($icon) === '') {
            throw InvalidNavigationItem::emptyIcon($this->id());
        }

        // Checked before the lookup: "home-line" is not a case either, but this error says how to fix it.
        if (str_ends_with($icon, '-line') || str_ends_with($icon, '-fill')) {
            throw InvalidNavigationItem::iconStyleVariant($this->id(), $icon);
        }

        return NavIcon::tryFrom($icon) ?? throw InvalidNavigationItem::unknownIcon($this->id(), $icon);
    }

    protected function resolveTarget(NavItem|string $target): string
    {
        $this->assertUnplaced();

        $id = $target instanceof NavItem ? $target->value : $target;

        if ($id === $this->id()) {
            throw InvalidNavigationItem::selfTarget($this->id());
        }

        return $id;
    }

    protected function assertUnplaced(): void
    {
        if ($this->isPlaced()) {
            throw InvalidNavigationItem::alreadyPlaced($this->id());
        }
    }
}
