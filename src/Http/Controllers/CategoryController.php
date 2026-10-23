<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Actions\DeleteCategory;
use Larasell\Admin\Data\View\CategoriesPageData;
use Larasell\Admin\Data\View\CategoryPageData;
use Larasell\Admin\Data\View\CreateCategoryPageData;
use Larasell\Admin\Data\ViewModel\CategoryData;
use Larasell\Admin\Data\ViewModel\CategoryDetailData;
use Larasell\Admin\Http\Requests\CategoryRequest;
use Larasell\Admin\Tables\Panel\CategoryProducts;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Translatable;
use Laravel\Head\Facades\Head;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

    /**
     * Shows the category to edit. A category that no longer exists, such as one deleted in
     * another tab, keeps the tree and explains itself on the page instead of a bare 404 page.
     */
    public function show(Request $request, string $category): SymfonyResponse
    {
        $category = Category::query()->find($category);

        if ($category === null) {
            Head::title('Category not found');

            // Inertia still renders a page that comes with an error status.
            return Inertia::render('Category/Show', new CategoryPageData(
                categories: $this->tree(),
                createUrl: route('larasell.admin.categories.create'),
            ))->toResponse($request)->setStatusCode(404);
        }

        Head::title($category->name->get());

        return Inertia::render('Category/Show', new CategoryPageData(
            categories: $this->tree(),
            createUrl: route('larasell.admin.categories.create'),
            category: CategoryDetailData::fromModel($category),
            updateUrl: route('larasell.admin.categories.update', $category),
            products: CategoryProducts::make($category),
            availableProductsUrl: route('larasell.admin.categories.available-products.index', $category),
            addProductUrl: route('larasell.admin.categories.products.store', $category),
        ))->toResponse($request);
    }

    /**
     * Creates a root category, or one inside the category given as ?parent=.
     */
    public function create(Request $request): Response
    {
        $parent = $request->filled('parent')
            ? Category::query()->findOrFail($request->integer('parent'))
            : null;

        if ($parent !== null) {
            Head::title("New category in {$parent->name->get()}");
        }

        return Inertia::render('Category/Create', new CreateCategoryPageData(
            categories: $this->tree(),
            createUrl: route('larasell.admin.categories.create'),
            storeUrl: route('larasell.admin.categories.store'),
            parent: $parent ? CategoryDetailData::fromModel($parent) : null,
        ));
    }

    /**
     * Creates a category, inside its parent when given, with the name and slug in the current locale.
     */
    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = Category::create([
            'parent_id' => $request->validated('parent_id'),
            'name' => Translatable::fromString($request->validated('name')),
            'slug' => Translatable::fromString($request->validated('slug')),
            'status' => $request->validated('status'),
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
     * @return array<int, CategoryData>
     */
    protected function tree(): array
    {
        // One query for the whole tree; the Category relations would query once per level.
        return CategoryData::tree(Category::query()->orderBy('id')->get());
    }
}
