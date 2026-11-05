<?php

namespace Larasell\Admin\Product\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Larasell\Admin\Platform\ContentLocale\AvailableLocales;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Search\Data\SearchMatchData;
use Larasell\Admin\Platform\Search\Data\SearchResultData;
use Larasell\Admin\Platform\Search\Http\Requests\SearchRequest;
use Larasell\Admin\Platform\Search\SearchQuery;
use Larasell\Admin\Product\Search\ProductSearch;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Models\Product;

/**
 * The command menu's product search: each product's name in the content locale, and what else
 * matched when the name didn't.
 */
class SearchProductsController
{
    public function __invoke(
        SearchRequest $request,
        ProductSearch $search,
        AvailableLocales $locales,
        #[CurrentContentLocale] Locale $locale,
    ): JsonResponse {
        $query = $request->toQuery($locale);

        $results = $search->search($query)->map(fn (Product $product): SearchResultData => new SearchResultData(
            $product->id,
            $product->name->get($locale->value),
            route('larasell.admin.products.edit', $product),
            $this->match($product, $query, $locales),
        ));

        return response()->json(['results' => $results->values()]);
    }

    private function match(Product $product, SearchQuery $query, AvailableLocales $locales): ?SearchMatchData
    {
        if ($query->matches($product->name->get($query->locale->value))) {
            return null;
        }

        foreach ($locales->all() as $locale) {
            $name = $product->name->all()[$locale->value] ?? null;

            if ($name !== null && $query->matches($name)) {
                return new SearchMatchData(\Locale::getDisplayName($locale->value, App::currentLocale()) ?: $locale->value, $name);
            }
        }

        $identifiers = [['SKU', $product->sku], ['Barcode', $product->barcode]];

        foreach ($product->variants->where('is_default', false) as $variant) {
            $identifiers[] = ['Variant SKU', $variant->sku];
            $identifiers[] = ['Variant barcode', $variant->barcode];
        }

        foreach ($identifiers as [$label, $value]) {
            if ($value !== null && $query->matches($value)) {
                return new SearchMatchData($label, $value);
            }
        }

        // Matched by something it doesn't show, e.g. a search engine's synonyms.
        return null;
    }
}
