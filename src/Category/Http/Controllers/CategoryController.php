<?php

namespace Larasell\Admin\Category\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Category\Actions\DeleteCategory;
use Larasell\Admin\Category\Data\CategoriesPageData;
use Larasell\Admin\Category\Data\CategoryData;
use Larasell\Admin\Category\Data\CategoryDetailData;
use Larasell\Admin\Category\Data\CategoryPageData;
use Larasell\Admin\Category\Data\CreateCategoryPageData;
use Larasell\Admin\Category\Data\MissingCategoryPageData;
use Larasell\Admin\Category\Http\Requests\CategoryRequest;
use Larasell\Admin\Category\Tables\CategoryProducts;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Translatable;
use Laravel\Head\Facades\Head;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CategoryController
{
    /**
     * Opens the top-most root category. Only a store without categories sees the index itself.
     */
    public function index(#[CurrentContentLocale] Locale $locale): Response|RedirectResponse
    {
        $first = Category::query()->whereNull('parent_id')->orderBy('position')->orderBy('id')->first();

        if ($first !== null) {
            return redirect()->route('larasell.admin.categories.show', $first);
        }

        return Inertia::render('Category/Index', new CategoriesPageData(
            categories: $this->tree($locale),
            createUrl: route('larasell.admin.categories.create'),
        ));
    }

    /**
     * Shows the category to edit. A category that no longer exists, such as one deleted in
     * another tab, keeps the tree and explains itself on the page instead of a bare 404 page.
     */
    public function show(Request $request, string $category, #[CurrentContentLocale] Locale $locale): SymfonyResponse
    {
        $category = Category::query()->find($category);

        if ($category === null) {
            Head::title(__('Category not found'));

            // Inertia still renders a page that comes with an error status.
            return Inertia::render('Category/Show', new MissingCategoryPageData(
                categories: $this->tree($locale),
                createUrl: route('larasell.admin.categories.create'),
            ))->toResponse($request)->setStatusCode(404);
        }

        Head::title($category->name->get($locale->value));

        return Inertia::render('Category/Show', new CategoryPageData(
            categories: $this->tree($locale),
            createUrl: route('larasell.admin.categories.create'),
            category: CategoryDetailData::fromModel($category, $locale),
            updateUrl: route('larasell.admin.categories.update', $category),
            products: CategoryProducts::make($category),
            availableProductsUrl: route('larasell.admin.categories.available-products.index', $category),
            addProductUrl: route('larasell.admin.categories.products.store', $category),
            createProductUrl: Product::query()->exists() ? null : $this->createProductUrl(),
        ))->toResponse($request);
    }

    /**
     * Creates a root category, or one inside the category given as ?parent=.
     */
    public function create(Request $request, #[CurrentContentLocale] Locale $locale): Response
    {
        $parent = $request->filled('parent')
            ? Category::query()->findOrFail($request->integer('parent'))
            : null;

        if ($parent !== null) {
            Head::title(__('New category in :category', ['category' => $parent->name->get($locale->value)]));
        }

        return Inertia::render('Category/Create', new CreateCategoryPageData(
            categories: $this->tree($locale),
            createUrl: route('larasell.admin.categories.create'),
            storeUrl: route('larasell.admin.categories.store'),
            indexUrl: route('larasell.admin.categories.index'),
            parent: $parent ? CategoryDetailData::fromModel($parent, $locale) : null,
            createProductUrl: $this->createProductUrl(),
        ));
    }

    /**
     * Creates a category, inside its parent when given, with the name and slug in the content locale.
     */
    public function store(CategoryRequest $request, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        $category = Category::create([
            'parent_id' => $request->validated('parent_id'),
            'name' => Translatable::fromString($request->validated('name'), $locale->value),
            'slug' => Translatable::fromString($request->validated('slug'), $locale->value),
            'status' => $request->validated('status'),
        ]);

        return redirect()->route('larasell.admin.categories.show', $category);
    }

    /**
     * Changes the name and slug in the content locale and keeps their other translations.
     */
    public function update(CategoryRequest $request, Category $category, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        $category->update([
            'name' => $category->name->with($locale->value, $request->validated('name')),
            'slug' => $category->slug->with($locale->value, $request->validated('slug')),
            'status' => $request->validated('status'),
        ]);

        return redirect()->route('larasell.admin.categories.show', $category);
    }

    /**
     * Deletes the category with all categories below it. Goes back, unless the previous page
     * was one of the deleted categories.
     */
    public function destroy(Request $request, Category $category, DeleteCategory $delete): RedirectResponse
    {
        $delete->handle($category, $request->string('confirmation')->value() ?: null);

        try {
            $previous = Route::getRoutes()->match(Request::create(url()->previous()));
        } catch (HttpException) {
            return back();
        }

        $shown = $previous->getName() === 'larasell.admin.categories.show' ? $previous->parameter('category') : null;

        if ($shown !== null && Category::query()->whereKey($shown)->doesntExist()) {
            return redirect()->route('larasell.admin.categories.index');
        }

        return back();
    }

    /**
     * Where a product is created, or null while the products are turned off in the config.
     */
    protected function createProductUrl(): ?string
    {
        return config('larasell.admin.products.enabled') ? route('larasell.admin.products.create') : null;
    }

    /**
     * The store's categories for the sidebar, named in the content locale.
     *
     * @return array<int, CategoryData>
     */
    protected function tree(Locale $locale): array
    {
        // One query for the whole tree; the Category relations would query once per level.
        return CategoryData::tree(Category::query()->orderBy('position')->orderBy('id')->get(), $locale);
    }
}
