<?php

namespace Larasell\Admin\Product\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Category\Data\CategoryOptionData;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Product\Data\CreateProductPageData;
use Larasell\Admin\Product\Data\EditProductPageData;
use Larasell\Admin\Product\Data\ProductDetailData;
use Larasell\Admin\Product\Http\Requests\ProductRequest;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Models\ProductImage;
use Larasell\Larasell\Price;
use Larasell\Larasell\Translatable;
use Laravel\Head\Facades\Head;

/**
 * Creates and edits products. The index is the Products table, which Navigation::index registers.
 */
class ProductController
{
    public function create(#[CurrentContentLocale] Locale $locale): Response
    {
        return Inertia::render('Product/Create', new CreateProductPageData(
            storeUrl: route('larasell.admin.products.store'),
            indexUrl: route('larasell.admin.products.index'),
            categories: $this->categories($locale),
            createCategoryUrl: config('larasell.admin.categories.enabled') ? route('larasell.admin.categories.create') : null,
            uploadImagesUrl: route('larasell.admin.products.images.store'),
        ));
    }

    /**
     * Creates a product with the name and slug in the content locale. The form has no price
     * yet, and every product needs one, so it starts at 0.
     */
    public function store(ProductRequest $request, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        $product = Product::create([
            'name' => Translatable::fromString($request->validated('name'), $locale->value),
            'slug' => Translatable::fromString($request->validated('slug'), $locale->value),
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
        $this->saveImageAlts($request);

        return redirect()->route('larasell.admin.products.edit', $product);
    }

    public function edit(Product $product, #[CurrentContentLocale] Locale $locale): Response
    {
        Head::title($product->name->get($locale->value));

        return Inertia::render('Product/Edit', new EditProductPageData(
            product: ProductDetailData::fromModel($product, $locale),
            updateUrl: route('larasell.admin.products.update', $product),
            indexUrl: route('larasell.admin.products.index'),
            categories: $this->categories($locale),
            createCategoryUrl: config('larasell.admin.categories.enabled') ? route('larasell.admin.categories.create') : null,
            uploadImagesUrl: route('larasell.admin.products.images.store'),
        ));
    }

    /**
     * Changes the name and slug in the content locale and keeps their other translations.
     */
    public function update(ProductRequest $request, Product $product, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        // The product checks each quantity limit against the other one it already has, so
        // clearing both first lets a new range lie wholly above or below the old one.
        $product->fill(['min_quantity' => null, 'max_quantity' => null])->update([
            'name' => $product->name->with($locale->value, $request->validated('name')),
            'slug' => $product->slug->with($locale->value, $request->validated('slug')),
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
        $this->saveImageAlts($request);

        return redirect()->route('larasell.admin.products.edit', $product);
    }

    /**
     * Saves the alt text the form gives the product's images.
     */
    protected function saveImageAlts(ProductRequest $request): void
    {
        foreach ($request->imageAlts() as $id => $alt) {
            ProductImage::query()->whereKey($id)->update(['alt' => $alt]);
        }
    }

    /**
     * The store's categories to pick the product's from, named in the content locale.
     *
     * @return array<int, CategoryOptionData>
     */
    protected function categories(Locale $locale): array
    {
        // One query for the whole tree; the Category relations would query once per level.
        return CategoryOptionData::tree(Category::query()->orderBy('position')->orderBy('id')->get(), $locale);
    }
}
