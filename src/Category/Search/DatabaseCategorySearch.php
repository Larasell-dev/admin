<?php

namespace Larasell\Admin\Category\Search;

use Illuminate\Support\Collection;
use Larasell\Admin\Platform\ContentLocale\AvailableLocales;
use Larasell\Admin\Platform\Search\SearchQuery;
use Larasell\Larasell\Models\Category;

/**
 * Searches the categories table, so it needs no search engine.
 */
class DatabaseCategorySearch implements CategorySearch
{
    public function __construct(private readonly AvailableLocales $locales) {}

    public function search(SearchQuery $query): Collection
    {
        return Category::query()
            ->where(function ($where) use ($query): void {
                foreach ($this->locales->all() as $locale) {
                    $query->orWhereMatches($where, "name->{$locale->value}");
                }
            })
            ->tap(fn ($categories) => $query->orderByMatching($categories, "name->{$query->locale->value}"))
            ->orderBy('id')
            ->limit($query->limit)
            ->get();
    }
}
