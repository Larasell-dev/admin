<?php

namespace Larasell\Admin\Search;

use Illuminate\Support\Collection;
use Larasell\Larasell\Models\Category;

/**
 * Finds categories, by their name in any locale, for the command menu.
 *
 * @public Bind another implementation in the container to search elsewhere, e.g. with Laravel Scout, or
 * extend() the bound one to change what it finds.
 */
interface CategorySearch
{
    /**
     * The best matches first, at most $query->limit of them.
     *
     * @return Collection<int, Category>
     */
    public function search(SearchQuery $query): Collection;
}
