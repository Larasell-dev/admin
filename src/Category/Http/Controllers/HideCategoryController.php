<?php

namespace Larasell\Admin\Category\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Catalog\Actions\UpdateCategory;
use Larasell\Larasell\Catalog\Actions\UpdateCategoryPayload;
use Larasell\Larasell\Catalog\CategoryId;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Enums\Visibility;

class HideCategoryController
{
    /**
     * Hides the category, so the storefront no longer shows it.
     */
    public function __invoke(Category $category, UpdateCategory $update, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $update->handle(CategoryId::of($category), new UpdateCategoryPayload(status: Visibility::Hidden), $origin);

        return back();
    }
}
