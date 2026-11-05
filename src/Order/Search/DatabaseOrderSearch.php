<?php

namespace Larasell\Admin\Order\Search;

use Illuminate\Support\Collection;
use Larasell\Admin\Platform\Search\SearchQuery;
use Larasell\Larasell\Models\Order;

/**
 * Searches the orders table, newest first, so it needs no search engine.
 */
class DatabaseOrderSearch implements OrderSearch
{
    public function search(SearchQuery $query): Collection
    {
        return Order::query()
            ->where(function ($where) use ($query): void {
                $query->orWhereMatches($where, 'number');
                $query->orWhereMatches($where, 'customer_name');
                $query->orWhereMatches($where, 'customer_email');
            })
            ->latest('id')
            ->limit($query->limit)
            ->get();
    }
}
