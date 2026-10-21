<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\CategoriesPageData;
use Larasell\Admin\Data\View\CategoryPageData;
use Larasell\Admin\Data\ViewModel\CategoryData;
use Larasell\Admin\Data\ViewModel\CategoryDetailData;
use Larasell\Larasell\Models\Category;

class CategoryController
{
    /**
     * Opens the top-most root category. Only a store without categories sees the index itself.
     */
    public function index(): Response|RedirectResponse
    {
        // Not Category::root(): that skips hidden categories, which the tree shows.
        $first = Category::query()->whereNull('parent_id')->orderBy('id')->first();

        if ($first !== null) {
            return redirect()->route('larasell.admin.categories.show', $first);
        }

        return Inertia::render('Categories', new CategoriesPageData(
            categories: $this->tree(),
        ));
    }

    public function show(Category $category): Response
    {
        return Inertia::render('Category', new CategoryPageData(
            categories: $this->tree(),
            category: CategoryDetailData::fromModel($category),
        ));
    }

    /**
     * @return array<int, CategoryData>
     */
    protected function tree(): array
    {
        // One query for the whole tree; the Category relations would query once per level.
        return CategoryData::tree(Category::query()->orderBy('id')->get());
    }
}
