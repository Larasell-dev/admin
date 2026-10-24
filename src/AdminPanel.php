<?php

namespace Larasell\Admin;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\Route;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidNavigationItem;
use Larasell\Admin\Http\Middleware\Authenticate;
use Larasell\Admin\Http\Middleware\HandleAdminInertiaRequests;
use Larasell\Admin\ValueObjects\NavigationItem;
use LogicException;

class AdminPanel
{
    /**
     * @var list<NavigationItem>
     */
    protected array $navigation = [];

    /**
     * Upper bound on navigation items, so ordering stays cheap and a runaway loop fails loudly.
     */
    public const NAVIGATION_MAX = 1_000;

    /**
     * Base page title of every admin route. Pages that set their own title
     * read "Categories - Admin"; pages without one read "Admin".
     */
    public const TITLE = ['value' => 'Admin', 'suffix' => ' - Admin', 'exact' => true];

    /**
     * @var list<string>
     */
    protected array $scripts = [];

    /**
     * @var list<string>
     */
    protected array $pagePaths = [];

    public static function resolve(): self
    {
        return app(self::class);
    }

    public function add(string $label, string $route, Icon|string $icon): NavigationItem
    {
        if (! str_contains($route, '.')) {
            $route = 'larasell.admin.'.$route;
        }

        foreach ($this->navigation as $existing) {
            if ($existing->id() === $route) {
                throw InvalidNavigationItem::duplicateId($route);
            }
        }

        self::assert(count($this->navigation) < self::NAVIGATION_MAX, 'Too many navigation items.');

        return $this->navigation[] = new NavigationItem($label, $route, $icon, $this);
    }

    /**
     * Top-level items in order, each with its children in order. Children are ordered among
     * themselves only, so their positions do not compete with top-level positions.
     *
     * @return list<array{id: string, label: string, url: string, icon: string, children: list<array{id: string, label: string, url: string, icon: string, children: array{}}>}>
     */
    public function navigationItems(): array
    {
        $byId = [];
        foreach ($this->navigation as $item) {
            $byId[$item->id()] = $item;
        }

        $topLevel = [];
        $children = [];
        foreach ($this->navigation as $item) {
            if ($item->parent !== null) {
                $parent = $byId[$item->parent] ?? throw InvalidNavigationItem::unknownParent($item->id(), $item->parent);

                if ($parent->parent !== null) {
                    throw InvalidNavigationItem::nestedTooDeep($item->id(), $parent->id());
                }
            }

            $target = $item->relativeTarget();
            if ($target !== null) {
                $targetItem = $byId[$target] ?? throw InvalidNavigationItem::unknownTarget($item->id(), $target);

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

            $tree[] = [
                ...$item->toArray(),
                'children' => array_map(fn (NavigationItem $child): array => [...$child->toArray(), 'children' => []], $group),
            ];
        }

        // Pair assertion: every registered item appears in the tree exactly once.
        self::assert($count === count($this->navigation), 'Nesting lost or duplicated navigation items.');

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

    public function routes(Closure $routes): void
    {
        Route::middleware(['web', HandleAdminInertiaRequests::class, Authenticate::class])
            ->prefix(config('larasell-admin.path'))
            ->name('larasell.admin.')
            ->withHead(title: self::TITLE)
            ->group($routes);
    }

    public function script(string $input): void
    {
        $this->scripts[] = $input;
    }

    /**
     * @return list<string>
     */
    public function scripts(): array
    {
        return $this->scripts;
    }

    public function pagePath(string $path): void
    {
        $this->pagePaths[] = $path;
    }

    /**
     * @return list<string>
     */
    public function pagePaths(): array
    {
        return $this->pagePaths;
    }

    public function panelVite(): Vite
    {
        return (clone app(Vite::class))
            ->useBuildDirectory('vendor/larasell-admin')
            ->useHotFile(dirname(__DIR__).'/public/hot');
    }
}
