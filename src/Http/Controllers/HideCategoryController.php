<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Category;

class HideCategoryController
{
    /**
     * Hides the category, so the storefront no longer shows it.
     */
    public function __invoke(Category $category): RedirectResponse
    {
        $category->update(['status' => Visibility::Hidden]);

        return back();
    }
}
