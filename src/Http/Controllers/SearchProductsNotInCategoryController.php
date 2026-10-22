<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;

class SearchProductsNotInCategoryController
{
    public function __invoke(Request $request, Category $category): JsonResponse
    {
        $search = $request->string('search')->trim()->value();

        $products = Product::query()
            ->whereDoesntHave('categories', fn ($query) => $query->whereKey($category->getKey()))
            // Names are translated, so it searches the current locale's name.
            ->when($search !== '', fn ($query) => $query->whereLike(
                'name->'.App::currentLocale(),
                '%'.addcslashes($search, '%_\\').'%',
            ))
            ->orderBy('id')
            ->limit(20)
            ->get()
            ->map(fn (Product $product): array => ['id' => $product->id, 'name' => $product->name->get()]);

        return response()->json(['products' => $products]);
    }
}
