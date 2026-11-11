<?php

namespace Larasell\Admin\Category\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Category\Http\Requests\AddProductToCategoryRequest;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Catalog\Actions\SetProductCategories;
use Larasell\Larasell\Catalog\CategoryId;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Catalog\ProductId;

class CategoryProductController
{
    /**
     * Adds an existing product to the category.
     */
    public function store(AddProductToCategoryRequest $request, Category $category, SetProductCategories $setCategories, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));

        $setCategories->handle(ProductId::of($product), CategoryId::many($product->categories()->allRelatedIds()->map(fn (mixed $id): int => (int) $id)->push($category->id)), $origin);

        return back();
    }
}
