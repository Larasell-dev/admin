<?php

namespace Larasell\Admin\Customer\Search;

use Illuminate\Support\Collection;
use Larasell\Admin\Platform\Search\SearchQuery;
use Larasell\Larasell\Customers\Models\Customer;

/**
 * Finds customers, by their number, name and email, for the command menu.
 *
 * @public Bind another implementation in the container to search elsewhere, e.g. with Laravel Scout.
 */
interface CustomerSearch
{
    /**
     * The best matches first, at most $query->limit of them.
     *
     * @return Collection<int, Customer>
     */
    public function search(SearchQuery $query): Collection;
}
