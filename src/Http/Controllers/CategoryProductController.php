<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Http\Requests\AddProductToCategoryRequest;
use Larasell\Larasell\Models\Category;

class CategoryProductController
{
    /**
     * Adds an existing product to the category.
     */
    public function store(AddProductToCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->products()->attach($request->integer('product_id'));

        return back();
    }
}
