<?php

namespace Larasell\Admin\Search;

use Illuminate\Support\Collection;
use Larasell\Larasell\Models\Customer;

/**
 * Searches the customers table, newest first, so it needs no search engine.
 */
class DatabaseCustomerSearch implements CustomerSearch
{
    public function search(SearchQuery $query): Collection
    {
        return Customer::query()
            // Spaces don't count, so the joined name also finds "Ada Lovelace" for "a lovel".
            ->where(fn ($where) => $query->orWhereMatches($where, 'first_name', 'last_name'))
            ->latest('id')
            ->limit($query->limit)
            ->get();
    }
}
