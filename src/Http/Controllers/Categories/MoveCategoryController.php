<?php

namespace Larasell\Admin\Http\Controllers\Categories;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Actions\Categories\MoveCategory;
use Larasell\Admin\Http\Requests\Categories\MoveCategoryRequest;
use Larasell\Larasell\Models\Category;

class MoveCategoryController
{
    /**
     * Moves the category, with everything below it, under another category or to the root.
     */
    public function __invoke(MoveCategoryRequest $request, Category $category, MoveCategory $move): RedirectResponse
    {
        $move->handle($category, $request->payload());

        return back();
    }
}
