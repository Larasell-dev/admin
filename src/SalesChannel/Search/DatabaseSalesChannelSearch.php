<?php

namespace Larasell\Admin\SalesChannel\Search;

use Illuminate\Support\Collection;
use Larasell\Admin\Platform\Search\SearchQuery;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;

/**
 * Searches the sales channels table, by name like the sidebar lists them, so it needs no search engine.
 */
class DatabaseSalesChannelSearch implements SalesChannelSearch
{
    public function search(SearchQuery $query): Collection
    {
        return SalesChannel::query()
            ->where(fn ($where) => $query->orWhereMatches($where, 'name'))
            ->orderBy('name')
            ->orderBy('id')
            ->limit($query->limit)
            ->get();
    }
}
