<?php

namespace Larasell\Admin\Platform\Search;

use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Search\Data\SearchData;

/**
 * The searches the command menu offers, each a command whose nested view asks its route. A
 * module adds its search while it's turned on, so the menu leaves out those that are off.
 *
 * @public Plugins add their own searches from a service provider's boot().
 */
class Searches
{
    /** @var array<string, array{label: string, icon: Icon, placeholder: string, route: string}> */
    private array $searches = [];

    /**
     * @param  string  $route  The name of a route that answers a SearchRequest with SearchResultData.
     */
    public function add(string $id, string $label, Icon $icon, string $placeholder, string $route): void
    {
        $this->searches[$id] = ['label' => $label, 'icon' => $icon, 'placeholder' => $placeholder, 'route' => $route];
    }

    /**
     * In the order they were added.
     *
     * @return list<SearchData>
     */
    public function all(): array
    {
        return array_map(
            fn (string $id, array $search): SearchData => new SearchData(
                $id,
                $search['label'],
                $search['icon']->name,
                $search['placeholder'],
                route($search['route']),
            ),
            array_keys($this->searches),
            $this->searches,
        );
    }
}
