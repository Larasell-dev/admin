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
     * In registration order, keyed by id.
     *
     * @var array<string, NavigationItem>
     */
    protected array $items = [];

    public static function resolve(): self
    {
        return app(self::class);
    }

    public function add(string $label, string $route, Icon|string $icon): NavigationItem
    {
        if (! str_contains($route, '.')) {
            $route = 'larasell.admin.'.$route;
        }

        if (isset($this->items[$route])) {
            throw InvalidNavigationItem::duplicateId($route);
        }

        self::assert(count($this->items) < self::MAX_ITEMS, 'Too many navigation items.');

        return $this->items[$route] = new NavigationItem($label, $route, $icon, $this);
    }

    /**
     * Top-level items in order, each with its children in order. Children are ordered among
     * themselves only, so their positions do not compete with top-level positions.
     *
     * @return list<NavigationItemData>
     */
    public function tree(): array
    {
        $topLevel = [];
        $children = [];
        foreach ($this->items as $item) {
            if ($item->parent !== null) {
                $parent = $this->items[$item->parent] ?? throw InvalidNavigationItem::unknownParent($item->id(), $item->parent);

                if ($parent->parent !== null) {
                    throw InvalidNavigationItem::nestedTooDeep($item->id(), $parent->id());
                }
            }

            $target = $item->relativeTarget();
            if ($target !== null) {
                $targetItem = $this->items[$target] ?? throw InvalidNavigationItem::unknownTarget($item->id(), $target);

                if ($targetItem->parent !== $item->parent) {
                    throw InvalidNavigationItem::targetNotSibling($item->id(), $target);
                }
            }

            if ($item->parent === null) {
                $topLevel[] = $item;
            } else {
                $children[$item->parent][] = $item;
            }
        }

        $tree = [];
        $count = 0;
        foreach ($this->ordered($topLevel) as $item) {
            $group = $this->ordered($children[$item->id()] ?? []);
            $count += 1 + count($group);

            $tree[] = $item->toData(children: array_map(fn (NavigationItem $child): NavigationItemData => $child->toData(), $group));
        }

        // Pair assertion: every registered item appears in the tree exactly once.
        self::assert($count === count($this->items), 'Nesting lost or duplicated navigation items.');

        return $tree;
    }

    /**
     * Orders one group of siblings: positioned items first (by position, then label), then
     * unpositioned items in registration order, then relative items inserted next to their targets.
     * Targets are already known to be in the group.
     *
     * @param  list<NavigationItem>  $items
     * @return list<NavigationItem>
     */
    protected function ordered(array $items): array
    {
        $positioned = array_values(array_filter($items, fn (NavigationItem $item): bool => $item->position !== null));
        usort($positioned, fn (NavigationItem $a, NavigationItem $b): int => [$a->position, $a->label] <=> [$b->position, $b->label]);

        $unpositioned = array_filter($items, fn (NavigationItem $item): bool => ! $item->isPlaced());
        $ordered = [...$positioned, ...array_values($unpositioned)];

        $relative = array_values(array_filter($items, fn (NavigationItem $item): bool => $item->relativeTarget() !== null));

        // Each pass places every item whose target is already placed; a pass without progress is a cycle.
        while ($relative !== []) {
            $pending = [];

            foreach ($relative as $item) {
                $index = array_search($item->relativeTarget(), array_map(fn (NavigationItem $placed): string => $placed->id(), $ordered), true);

                if ($index === false) {
                    $pending[] = $item;

                    continue;
                }

                array_splice($ordered, $item->before !== null ? $index : $index + 1, 0, [$item]);
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

        $previous = NavigationItem::POSITION_MIN;
        foreach ($ordered as $item) {
            if ($item->position !== null) {
                self::assert($item->position >= $previous, 'Navigation positions are not ascending.');
                $previous = $item->position;
            }
        }

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
