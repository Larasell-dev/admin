<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\CreateProductPageData;
use Larasell\Admin\Data\View\EditProductPageData;
use Larasell\Admin\Data\ViewModel\CategoryData;
use Larasell\Admin\Data\ViewModel\ProductDetailData;
use Larasell\Admin\Http\Requests\ProductRequest;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Price;
use Larasell\Larasell\Translatable;
use Laravel\Head\Facades\Head;

/**
 * Creates and edits products. The index is the Products table, which Navigation::index registers.
 */
class ProductController
{
    public function create(): Response
    {
        return Inertia::render('Product/Create', new CreateProductPageData(
            storeUrl: route('larasell.admin.products.store'),
            indexUrl: route('larasell.admin.products.index'),
            categories: $this->categories(),
            createCategoryUrl: route('larasell.admin.categories.create'),
            uploadImagesUrl: route('larasell.admin.products.images.store'),
        ));
    }

    /**
     * Creates a product with the name and slug in the current locale. The form has no price
     * yet, and every product needs one, so it starts at 0.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $product = Product::create([
            'name' => Translatable::fromString($request->validated('name')),
            'slug' => Translatable::fromString($request->validated('slug')),
            'status' => $request->validated('status'),
            'weight' => $request->weight(),
            'dimensions' => $request->dimensions(),
            'sku' => $request->validated('sku'),
            'barcode' => $request->validated('barcode'),
            ...$request->stock(),
            'price' => Price::of(0),
        ]);

        $product->categories()->attach($request->categoryIds());
        $product->images()->attach($request->images());

        return redirect()->route('larasell.admin.products.edit', $product);
    }

    public function edit(Product $product): Response
    {
        Head::title($product->name->get());

        return Inertia::render('Product/Edit', new EditProductPageData(
            product: ProductDetailData::fromModel($product),
            updateUrl: route('larasell.admin.products.update', $product),
            indexUrl: route('larasell.admin.products.index'),
            categories: $this->categories(),
            createCategoryUrl: route('larasell.admin.categories.create'),
            uploadImagesUrl: route('larasell.admin.products.images.store'),
        ));
    }

    /**
     * Changes the name and slug in the current locale and keeps their other translations.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $locale = App::currentLocale();

        // The product checks each quantity limit against the other one it already has, so
        // clearing both first lets a new range lie wholly above or below the old one.
        $product->fill(['min_quantity' => null, 'max_quantity' => null])->update([
            'name' => $product->name->with($locale, $request->validated('name')),
            'slug' => $product->slug->with($locale, $request->validated('slug')),
            'status' => $request->validated('status'),
            'weight' => $request->weight(),
            'dimensions' => $request->dimensions(),
            'sku' => $request->validated('sku'),
            'barcode' => $request->validated('barcode'),
            ...$request->stock(),
        ]);

        $product->categories()->sync($request->categoryIds());
        // Also moves the images the product keeps to their new positions.
        $product->images()->sync($request->images());

        return redirect()->route('larasell.admin.products.edit', $product);
    }

    /**
     * The store's categories to pick the product's from.
     *
     * @return array<int, CategoryData>
     */
    protected function categories(): array
    {
        // One query for the whole tree; the Category relations would query once per level.
        return CategoryData::tree(Category::query()->orderBy('id')->get());
    }
}
