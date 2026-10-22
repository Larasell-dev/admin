<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\CategoriesPageData;
use Larasell\Admin\Data\View\CategoryPageData;
use Larasell\Admin\Data\View\CreateCategoryPageData;
use Larasell\Admin\Data\ViewModel\CategoryData;
use Larasell\Admin\Data\ViewModel\CategoryDetailData;
use Larasell\Admin\Http\Requests\CategoryRequest;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Translatable;

class CategoryController
{
    /**
     * Opens the top-most root category. Only a store without categories sees the index itself.
     */
    public function index(): Response|RedirectResponse
    {
        $first = Category::query()->whereNull('parent_id')->orderBy('id')->first();

        if ($first !== null) {
            return redirect()->route('larasell.admin.categories.show', $first);
        }

        return Inertia::render('Category/Index', new CategoriesPageData(
            categories: $this->tree(),
            createUrl: route('larasell.admin.categories.create'),
        ));
    }

    public function show(Category $category): Response
    {
        return Inertia::render('Category/Show', new CategoryPageData(
            categories: $this->tree(),
            createUrl: route('larasell.admin.categories.create'),
            category: CategoryDetailData::fromModel($category),
            updateUrl: route('larasell.admin.categories.update', $category),
        ));
    }

    public function create(): Response
    {
        return Inertia::render('Category/Create', new CreateCategoryPageData(
            categories: $this->tree(),
            createUrl: route('larasell.admin.categories.create'),
            storeUrl: route('larasell.admin.categories.store'),
        ));
    }

    /**
     * Creates a root category with the name and slug in the current locale.
     */
    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create([
            'name' => Translatable::fromString($request->validated('name')),
            'slug' => Translatable::fromString($request->validated('slug')),
            'status' => $this->visibility($request),
        ]);

        return redirect()->route('larasell.admin.categories.show', $category);
    }

    /**
     * Changes the name and slug in the current locale and keeps their other translations.
     */
    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $locale = App::currentLocale();

        $category->update([
            'name' => $category->name->with($locale, $request->validated('name')),
            'slug' => $category->slug->with($locale, $request->validated('slug')),
            'status' => $this->visibility($request),
        ]);

        return redirect()->route('larasell.admin.categories.show', $category);
    }

    /**
     * An unchecked visibility checkbox isn't submitted, so a missing value hides the category.
     */
    protected function visibility(CategoryRequest $request): Visibility
    {
        return $request->boolean('visible') ? Visibility::Visible : Visibility::Hidden;
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
