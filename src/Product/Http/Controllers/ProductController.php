<?php

namespace Larasell\Admin\Product\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Category\Data\CategoryOptionData;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Admin\Platform\Sections\SectionForm;
use Larasell\Admin\Platform\Sections\Sections;
use Larasell\Admin\Platform\Support\Assert;
use Larasell\Admin\Product\Data\CreateProductPageData;
use Larasell\Admin\Product\Data\EditProductPageData;
use Larasell\Admin\Product\Data\ProductDetailData;
use Larasell\Admin\Product\Events\ProductSaved;
use Larasell\Admin\Product\Exceptions\ProductNotSavedAtomically;
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
    public function __construct(private readonly Sections $sections) {}

    public function create(#[CurrentContentLocale] Locale $locale): Response
    {
        return Inertia::render('Product/Create', new CreateProductPageData(
            storeUrl: route('larasell.admin.products.store'),
            indexUrl: route('larasell.admin.products.index'),
            categories: $this->categories($locale),
            createCategoryUrl: config('larasell.admin.categories.enabled') ? route('larasell.admin.categories.create') : null,
            uploadImagesUrl: route('larasell.admin.products.images.store'),
            sections: $this->sections->data(SectionForm::Product, null, new SectionContext($locale)),
        ));
    }

    /**
     * Creates a product with the name and slug in the content locale. The form has no price
     * yet, and every product needs one, so it starts at 0.
     */
    public function store(ProductRequest $request, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        // With the sections' own fields, so a section that fails leaves no product behind.
        $product = DB::transaction(function () use ($request, $locale): Product {
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
            $this->saved($request, $product, true, $locale);

            return $product;
        });

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
            sections: $this->sections->data(SectionForm::Product, $product, new SectionContext($locale)),
        ));
    }

    /**
     * Changes the name and slug in the content locale and keeps their other translations.
     */
    public function update(ProductRequest $request, Product $product, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        // With the sections' own fields, so a section that fails leaves the product unchanged.
        DB::transaction(function () use ($request, $product, $locale): void {
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
            $this->saved($request, $product, false, $locale);
        });

        return redirect()->route('larasell.admin.products.edit', $product);
    }

    /**
     * Lets the page's sections save their own fields, in the same transaction.
     */
    protected function saved(ProductRequest $request, Product $product, bool $created, Locale $locale): void
    {
        // The promise ProductSaved documents, and asserts again: its listeners run in the save's transaction.
        Assert::that(DB::transactionLevel() > 0, ProductNotSavedAtomically::outsideTransaction(...));

        event(new ProductSaved($product, $created, new SectionContext($locale), $request->sections()));
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
