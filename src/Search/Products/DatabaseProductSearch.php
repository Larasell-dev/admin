<?php

namespace Larasell\Admin\Search\Products;

use Illuminate\Support\Collection;
use Larasell\Admin\ContentLocale\AvailableLocales;
use Larasell\Admin\Search\SearchQuery;
use Larasell\Larasell\Models\Product;

/**
 * Searches the products table, so it needs no search engine.
 */
class DatabaseProductSearch implements ProductSearch
{
    public function __construct(private readonly AvailableLocales $locales) {}

    public function search(SearchQuery $query): Collection
    {
        return Product::query()
            ->where(function ($where) use ($query): void {
                foreach ($this->locales->all() as $locale) {
                    $query->orWhereMatches($where, "name->{$locale->value}");
                }

                // The default variant has the product's own SKU and barcode.
                $where->orWhereHas('variants', function ($variants) use ($query): void {
                    $variants->where(function ($variant) use ($query): void {
                        $query->orWhereMatches($variant, 'sku');
                        $query->orWhereMatches($variant, 'barcode');
                    });
                });
            })
            ->tap(fn ($products) => $query->orderByMatching($products, "name->{$query->locale->value}"))
            ->orderBy('id')
            ->limit($query->limit)
            ->with('variants')
            ->get();
    }
}
