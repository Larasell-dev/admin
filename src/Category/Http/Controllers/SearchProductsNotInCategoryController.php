<?php

namespace Larasell\Admin\Category\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Larasell\Admin\Platform\ContentLocale\AvailableLocales;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Locale;

class SearchProductsNotInCategoryController
{
    public function __invoke(Request $request, Category $category, #[CurrentContentLocale] Locale $locale, AvailableLocales $locales): JsonResponse
    {
        $search = $request->string('search')->trim()->value();
        $pattern = '%'.addcslashes($search, '%_\\').'%';
        $default = $locales->default()->value;

        $products = Product::query()
            ->whereDoesntHave('categories', fn ($query) => $query->whereKey($category->getKey()))
            // Searches the names the list shows: in the content locale, or in the default locale
            // for products without one in it.
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('name->'.$locale->value, $pattern)
                ->when($locale->value !== $default, fn ($query) => $query->orWhere(fn ($query) => $query
                    ->whereNull('name->'.$locale->value)
                    ->whereLike('name->'.$default, $pattern)))))
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->map(fn (Product $product): array => ['id' => $product->id, 'name' => $product->name->get($locale->value)]);

        return response()->json(['products' => $products]);
    }
}
