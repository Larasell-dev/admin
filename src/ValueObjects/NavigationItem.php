<?php

namespace Larasell\Admin\ValueObjects;

use Closure;
use Illuminate\Support\Str;
use Larasell\Admin\Data\ViewModel\NavigationItemData;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Exceptions\InvalidNavigationItem;
use Larasell\Admin\Navigation;
use Larasell\Admin\NavigationGroup;
use LogicException;

class NavigationItem
{
    public const POSITION_MIN = 0;

    public const POSITION_MAX = 1_000_000;

    /*
     * Placement is protected, so it only changes through position(), before(), after() and
     * under(), which check it; read it with the getters below.
     */
    protected ?int $position = null;

    protected ?string $before = null;

    protected ?string $after = null;

    /**
     * The id of the top-level item this item is nested under, if any.
     */
    protected ?string $parent = null;

    public readonly Icon $icon;

    /**
     * @param  Icon|string  $icon  A Icon case or its Remix icon name, e.g. "home"; the sidebar renders it filled when active, line otherwise.
     */
    public function __construct(
        public readonly string $label,
        public readonly string $route,
        Icon|string $icon,
        protected ?Navigation $navigation = null,
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
        $this->navigation?->assertOpen($this->id());

        $this->assertUnplaced();

        if ($position < self::POSITION_MIN || $position > self::POSITION_MAX) {
            throw InvalidNavigationItem::positionOutOfRange($this->id(), $position, self::POSITION_MIN, self::POSITION_MAX);
        }

        $this->position = $position;
        $this->assertInvariants();

        return $this;
    }

    public function before(NavItem|string $target): static
    {
        $this->navigation?->assertOpen($this->id());

        $this->before = $this->resolveTarget($target);
        $this->assertInvariants();

        return $this;
    }

    public function after(NavItem|string $target): static
    {
        $this->navigation?->assertOpen($this->id());

        $this->after = $this->resolveTarget($target);
        $this->assertInvariants();

        return $this;
    }

    /**
     * Nests this item one level under a top-level item. Its position, before() and after()
     * then apply among that item's children only.
     */
    public function under(NavItem|string $parent): static
    {
        $this->navigation?->assertOpen($this->id());

        $id = $parent instanceof NavItem ? $parent->value : $parent;

        if ($this->parent !== null) {
            throw InvalidNavigationItem::alreadyNested($this->id(), $this->parent);
        }

        if ($id === $this->id()) {
            throw InvalidNavigationItem::selfParent($this->id());
        }

        $this->parent = $id;
        $this->assertInvariants();

        return $this;
    }

    /**
     * Registers children of this item: each item added to the group is nested under this one.
     *
     * @param  Closure(NavigationGroup): mixed  $define
     */
    public function children(Closure $define): static
    {
        $this->navigation?->assertOpen($this->id());

        if ($this->navigation === null) {
            throw new LogicException("Navigation item [{$this->id()}] was not registered through the navigation, so it cannot have children.");
        }

        $define(new NavigationGroup($this->navigation, $this->id()));

        return $this;
    }

    public function fixedPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Whether a relative placement puts this item before its target rather than after it.
     */
    public function placesBefore(): bool
    {
        return $this->before !== null;
    }

    public function parentId(): ?string
    {
        return $this->parent;
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
     * The item as the sidebar receives it. The icon is sent as its case name, which
     *
     * @larasell-dev/ui/icons exports with a Fill or Line suffix.
     *
     * @param  list<NavigationItemData>  $children
     */
    public function toData(array $children = []): NavigationItemData
    {
        return new NavigationItemData(
            id: $this->id(),
            label: $this->label,
            url: route($this->route),
            icon: $this->icon->name,
            children: $children,
        );
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

    /**
     * Always-on checks of what the setters already enforce, so a future setter that skips a
     * check fails here instead of producing a broken navigation.
     */
    protected function assertInvariants(): void
    {
        $placements = count(array_filter([$this->position, $this->before, $this->after], fn (int|string|null $value): bool => $value !== null));

        if ($placements > 1) {
            throw new LogicException("Navigation item [{$this->id()}] has more than one placement.");
        }

        if ($this->position !== null && ($this->position < self::POSITION_MIN || $this->position > self::POSITION_MAX)) {
            throw new LogicException("Navigation item [{$this->id()}] has a position outside its range.");
        }

        if (in_array($this->id(), [$this->parent, $this->before, $this->after], true)) {
            throw new LogicException("Navigation item [{$this->id()}] refers to itself.");
        }
    }

    protected function assertUnplaced(): void
    {
        if ($this->isPlaced()) {
            throw InvalidNavigationItem::alreadyPlaced($this->id());
        }
    }
}
