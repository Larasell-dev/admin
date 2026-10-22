<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;

class CategoryProductController
{
    /**
     * Adds an existing product to the category.
     */
    public function store(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                Rule::exists(Product::class, 'id'),
                Rule::unique('larasell_category_product', 'product_id')->where('category_id', $category->getKey()),
            ],
        ], [
            'product_id.unique' => 'The product is already in this category.',
        ]);

        $category->products()->attach($validated['product_id']);

        return back();
    }
}
