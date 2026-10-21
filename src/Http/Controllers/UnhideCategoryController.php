<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Category;

class UnhideCategoryController
{
    /**
     * Makes a hidden category visible in the storefront again.
     */
    public function __invoke(Category $category): RedirectResponse
    {
        $category->update(['status' => Visibility::Visible]);

        return back();
    }
}
