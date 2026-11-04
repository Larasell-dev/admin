<?php

namespace Larasell\Admin\Search;

use Illuminate\Support\Collection;
use Larasell\Larasell\Models\Product;

/**
 * Finds products, by their name in any locale, their SKU and barcode, and those of their variants, for the command menu.
 *
 * @public Bind another implementation in the container to search elsewhere, e.g. with Laravel Scout, or
 * extend() the bound one to change what it finds.
 */
interface ProductSearch
{
    /**
     * The best matches first, at most $query->limit of them.
     *
     * @return Collection<int, Product>
     */
    public function search(SearchQuery $query): Collection;
}
