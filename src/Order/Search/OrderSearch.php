<?php

namespace Larasell\Admin\Order\Search;

use Illuminate\Support\Collection;
use Larasell\Admin\Search\SearchQuery;
use Larasell\Larasell\Models\Order;

/**
 * Finds orders, by their number and their customer's name and email, for the command menu.
 *
 * @public Bind another implementation in the container to search elsewhere, e.g. with Laravel Scout, or
 * extend() the bound one to change what it finds.
 */
interface OrderSearch
{
    /**
     * The best matches first, at most $query->limit of them.
     *
     * @return Collection<int, Order>
     */
    public function search(SearchQuery $query): Collection;
}
