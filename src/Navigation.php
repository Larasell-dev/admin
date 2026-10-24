<?php

namespace Larasell\Admin;

use Larasell\Admin\Data\ViewModel\NavigationItemData;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidNavigationItem;
use Larasell\Admin\ValueObjects\NavigationItem;
use LogicException;

/**
 * The admin panel's sidebar navigation. Items are registered in any order and stay flat,
 * each knowing its parent; tree() validates them and returns them nested and ordered.
 */
class Navigation
{
    /**
     * Upper bound on navigation items, so ordering stays cheap and a runaway loop fails loudly.
     */
    public const MAX_ITEMS = 1_000;

    /**
     * Upper bound on the children of one item; more would not fit a sidebar anyway.
     */
    public const MAX_CHILDREN = 100;

    /**
     * Group key for top-level items; ids are route names, so never empty.
     */
    protected const TOP_LEVEL = '';

    /**
     * In registration order, keyed by id.
     *
     * @var array<string, NavigationItem>
     */
    protected array $items = [];

    /**
     * Set once the app has booted; after that, items can no longer be added or changed.
     */
    protected bool $frozen = false;

    public static function resolve(): self
    {
        return app(self::class);
    }

    public function add(string $label, string $route, Icon|string $icon): NavigationItem
    {
        if (! str_contains($route, '.')) {
            $route = 'larasell.admin.'.$route;
        }

        $this->assertOpen($route);

        if (isset($this->items[$route])) {
            throw InvalidNavigationItem::duplicateId($route);
        }

        self::assert(count($this->items) < self::MAX_ITEMS, 'Too many navigation items.');

        return $this->items[$route] = new NavigationItem($label, $route, $icon, $this);
    }

    public function freeze(): void
    {
        $this->frozen = true;
    }

    /**
     * Throws if the navigation is frozen; items call this before every change.
     */
    public function assertOpen(string $id): void
    {
        if ($this->frozen) {
            throw InvalidNavigationItem::frozen($id);
        }
    }

    /**
     * Top-level items in order, each with its children in order. Children are ordered among
     * themselves only, so their positions do not compete with top-level positions.
     *
     * @return list<NavigationItemData>
     */
    public function tree(): array
    {
        $this->assertParentsAreTopLevel();
        $this->assertTargetsAreSiblings();

        $groups = [];
        foreach ($this->items as $item) {
            $groups[$item->parentId() ?? self::TOP_LEVEL][] = $item;
        }

        foreach ($groups as $parentId => $group) {
            self::assert(
                $parentId === self::TOP_LEVEL || count($group) <= self::MAX_CHILDREN,
                "Navigation item [{$parentId}] has more than ".self::MAX_CHILDREN.' children.',
            );
        }

        $tree = array_map(
            fn (NavigationItem $item): NavigationItemData => $item->toData(
                children: array_map(fn (NavigationItem $child): NavigationItemData => $child->toData(), $this->ordered($groups[$item->id()] ?? [])),
            ),
            $this->ordered($groups[self::TOP_LEVEL] ?? []),
        );

        // Pair assertion: every registered item appears in the tree exactly once.
        $count = count($tree) + array_sum(array_map(fn (NavigationItemData $item): int => count($item->children), $tree));
        self::assert($count === count($this->items), 'Nesting lost or duplicated navigation items.');

        return $tree;
    }

    /**
     * Every parent is registered and top-level itself, so nesting is one level deep.
     */
    protected function assertParentsAreTopLevel(): void
    {
        $parents = array_filter(array_map(fn (NavigationItem $item): ?string => $item->parentId(), $this->items));

        foreach ($parents as $id => $parentId) {
            $parent = $this->items[$parentId] ?? throw InvalidNavigationItem::unknownParent($id, $parentId);

            if ($parent->parentId() !== null) {
                throw InvalidNavigationItem::nestedTooDeep($id, $parentId);
            }
        }
    }

    /**
     * Every before() or after() target is registered and has the same parent as the item.
     */
    protected function assertTargetsAreSiblings(): void
    {
        $targets = array_filter(array_map(fn (NavigationItem $item): ?string => $item->relativeTarget(), $this->items));

        foreach ($targets as $id => $targetId) {
            $target = $this->items[$targetId] ?? throw InvalidNavigationItem::unknownTarget($id, $targetId);

            if ($target->parentId() !== $this->items[$id]->parentId()) {
                throw InvalidNavigationItem::targetNotSibling($id, $targetId);
            }
        }
    }

    /**
     * Orders one group of siblings: positioned items first (by position, then id, so translated
     * labels don't change the order), then unpositioned items in registration order, then
     * relative items inserted next to their targets.
     * Targets are already known to be in the group.
     *
     * @param  list<NavigationItem>  $items
     * @return list<NavigationItem>
     */
    protected function ordered(array $items): array
    {
        $positioned = array_values(array_filter($items, fn (NavigationItem $item): bool => $item->fixedPosition() !== null));
        usort($positioned, fn (NavigationItem $a, NavigationItem $b): int => [$a->fixedPosition(), $a->id()] <=> [$b->fixedPosition(), $b->id()]);

        $unpositioned = array_filter($items, fn (NavigationItem $item): bool => ! $item->isPlaced());
        $ordered = [...$positioned, ...array_values($unpositioned)];

        $relative = array_values(array_filter($items, fn (NavigationItem $item): bool => $item->relativeTarget() !== null));

        // Each pass places every item whose target is already placed; a pass without progress is a cycle.
        // So there are at most as many passes as relative items.
        $passesLeft = count($relative);
        while ($relative !== []) {
            self::assert($passesLeft-- > 0, 'Navigation placement did not finish within its pass limit.');
            $pending = [];

            foreach ($relative as $item) {
                $index = array_search($item->relativeTarget(), array_map(fn (NavigationItem $placed): string => $placed->id(), $ordered), true);

                if ($index === false) {
                    $pending[] = $item;

                    continue;
                }

                array_splice($ordered, $item->placesBefore() ? $index : $index + 1, 0, [$item]);
            }

            if (count($pending) === count($relative)) {
                $cycle = array_map(fn (NavigationItem $item): string => $item->id(), $pending);
                sort($cycle);

                throw InvalidNavigationItem::placementCycle($cycle);
            }

            $relative = $pending;
        }

        // Pair assertions: the output is a permutation of the input, and positions never decrease.
        self::assert(count($ordered) === count($items), 'Ordering lost or duplicated navigation items.');
        self::assert(
            count(array_unique(array_map(fn (NavigationItem $item): string => $item->id(), $ordered))) === count($ordered),
            'Ordering duplicated a navigation item.',
        );

        $positions = array_values(array_filter(array_map(fn (NavigationItem $item): ?int => $item->fixedPosition(), $ordered), fn (?int $position): bool => $position !== null));
        $ascending = $positions;
        sort($ascending);
        self::assert($positions === $ascending, 'Navigation positions are not ascending.');

        return $ordered;
    }

    /**
     * Always-on internal invariant check; PHP's assert() is compiled out in production.
     */
    protected static function assert(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new LogicException($message);
        }
    }
}
