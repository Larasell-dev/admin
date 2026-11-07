<?php

namespace Larasell\Admin\Category\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Category\Http\Requests\MoveCategoryRequest;
use Larasell\Larasell\Catalog\Actions\MoveCategory;
use Larasell\Larasell\Catalog\CategoryId;
use Larasell\Larasell\Catalog\Models\Category;

class MoveCategoryController
{
    /**
     * Moves the category, with everything below it, under another category or to the root.
     */
    public function __invoke(MoveCategoryRequest $request, Category $category, MoveCategory $move): RedirectResponse
    {
        $move->handle(CategoryId::of($category), $request->payload());

        return back();
    }
}
