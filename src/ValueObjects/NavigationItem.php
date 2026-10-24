<?php

namespace Larasell\Admin\ValueObjects;

use Closure;
use Illuminate\Support\Str;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Exceptions\InvalidNavigationItem;
use Larasell\Admin\NavigationGroup;
use LogicException;

class NavigationItem
{
    public const POSITION_MIN = 0;

    public const POSITION_MAX = 1_000_000;

    public ?int $position = null;

    public ?string $before = null;

    public ?string $after = null;

    /**
     * The id of the top-level item this item is nested under, if any.
     */
    public ?string $parent = null;

    public Icon $icon;

    /**
     * @param  Icon|string  $icon  A Icon case or its Remix icon name, e.g. "home"; the sidebar renders it filled when active, line otherwise.
     */
    public function __construct(
        public string $label,
        public string $route,
        Icon|string $icon,
        protected ?AdminPanel $panel = null,
    ) {
        if (trim($label) === '') {
            throw InvalidNavigationItem::emptyLabel();
        }

        if (trim($route) === '') {
            throw InvalidNavigationItem::emptyRoute($label);
        }

        $this->icon = $icon instanceof Icon ? $icon : $this->resolveIcon($icon);
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
     * Nests this item one level under a top-level item. Its position, before() and after()
     * then apply among that item's children only.
     */
    public function under(NavItem|string $parent): static
    {
        $id = $parent instanceof NavItem ? $parent->value : $parent;

        if ($this->parent !== null) {
            throw InvalidNavigationItem::alreadyNested($this->id(), $this->parent);
        }

        if ($id === $this->id()) {
            throw InvalidNavigationItem::selfParent($this->id());
        }

        $this->parent = $id;

        return $this;
    }

    /**
     * Registers children of this item: each item added to the group is nested under this one.
     *
     * @param  Closure(NavigationGroup): mixed  $define
     */
    public function children(Closure $define): static
    {
        if ($this->panel === null) {
            throw new LogicException("Navigation item [{$this->id()}] was not registered through the admin panel, so it cannot have children.");
        }

        $define(new NavigationGroup($this->panel, $this->id()));

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

    protected function resolveIcon(string $icon): Icon
    {
        if (Str::trim($icon) === '') {
            throw InvalidNavigationItem::emptyIcon($this->id());
        }

        // Checked before the lookup: "home-line" is not a case either, but this error says how to fix it.
        if (Str::endsWith($icon, ['-line', '-fill'])) {
            throw InvalidNavigationItem::iconStyleVariant($this->id(), $icon);
        }

        return Icon::tryFrom($icon) ?? throw InvalidNavigationItem::unknownIcon($this->id(), $icon);
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
