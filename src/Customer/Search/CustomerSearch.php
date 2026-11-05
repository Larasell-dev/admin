<?php

namespace Larasell\Admin\Customer\Search;

use Illuminate\Support\Collection;
use Larasell\Admin\Search\SearchQuery;
use Larasell\Larasell\Models\Customer;

/**
 * Finds customers, by their name, for the command menu.
 *
 * @public Bind another implementation in the container to search elsewhere, e.g. with Laravel Scout, or
 * extend() the bound one to change what it finds.
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
