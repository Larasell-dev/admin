<?php

namespace Larasell\Admin\Category\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Larasell\Catalog\Actions\UpdateCategory;
use Larasell\Larasell\Catalog\Actions\UpdateCategoryPayload;
use Larasell\Larasell\Catalog\CategoryId;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Enums\Visibility;

class UnhideCategoryController
{
    /**
     * Makes a hidden category visible in the storefront again.
     */
    public function __invoke(Category $category, UpdateCategory $update): RedirectResponse
    {
        $update->handle(CategoryId::of($category), new UpdateCategoryPayload(status: Visibility::Visible));

        return back();
    }
}
