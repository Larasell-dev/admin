<?php

namespace Larasell\Admin\SalesChannel\Search;

use Illuminate\Support\Collection;
use Larasell\Admin\Platform\Search\SearchQuery;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;

/**
 * Finds sales channels, by their name, for the command menu.
 *
 * @public Bind another implementation in the container to search elsewhere, e.g. with Laravel Scout, or
 * extend() the bound one to change what it finds.
 */
interface SalesChannelSearch
{
    /**
     * The best matches first, at most $query->limit of them.
     *
     * @return Collection<int, SalesChannel>
     */
    public function search(SearchQuery $query): Collection;
}
