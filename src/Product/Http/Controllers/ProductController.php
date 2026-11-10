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
use Larasell\Larasell\Catalog\Actions\CreateProduct;
use Larasell\Larasell\Catalog\Actions\CreateProductPayload;
use Larasell\Larasell\Catalog\Actions\SetProductCategories;
use Larasell\Larasell\Catalog\Actions\UpdateProduct;
use Larasell\Larasell\Catalog\Actions\UpdateProductPayload;
use Larasell\Larasell\Catalog\CategoryId;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Catalog\ProductId;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Price;
use Larasell\Larasell\RichText\TranslatableRichText;
use Larasell\Larasell\Translatable;
use Laravel\Head\Facades\Head;

/**
 * Creates and edits products. The index is the Products table, which Navigation::index registers.
 */
class ProductController
{
    public function __construct(
        private readonly Sections $sections,
        private readonly CreateProduct $createProduct,
        private readonly UpdateProduct $updateProduct,
        private readonly SetProductCategories $setCategories,
    ) {}

    public function create(#[CurrentContentLocale] Locale $locale): Response
    {
        return Inertia::render('Product/Pages/Create', new CreateProductPageData(
            storeUrl: route('larasell.admin.products.store'),
            indexUrl: route('larasell.admin.products.index'),
            categories: $this->categories($locale),
            createCategoryUrl: config('larasell.admin.categories.enabled') ? route('larasell.admin.categories.create') : null,
            sections: $this->sections->data(SectionForm::Product, null, new SectionContext($locale)),
        ));
    }

    /**
     * Creates a product with the name in the content locale, through the core's actions, which
     * also give it its URLs. The form has no price yet, and every product needs one, so it starts at 0.
     */
    public function store(ProductRequest $request, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        // With the sections' own fields, so a section that fails leaves no product behind.
        $product = DB::transaction(function () use ($request, $locale): Product {
            $product = $this->createProduct->handle(new CreateProductPayload([
                'name' => Translatable::fromString($request->validated('name'), $locale->value),
                'status' => $request->validated('status'),
                'description' => $request->description() === null ? null : TranslatableRichText::of($request->description(), $locale->value),
                'weight' => $request->weight(),
                'dimensions' => $request->dimensions(),
                'sku' => $request->validated('sku'),
                'barcode' => $request->validated('barcode'),
                ...$request->stock(),
                'price' => Price::of(0),
            ], $request->attachments()));

            $this->saveRelations($request, $product);
            $this->saved($request, $product, true, $locale);

            return $product;
        });

        return redirect()->route('larasell.admin.products.edit', $product);
    }

    public function edit(Product $product, #[CurrentContentLocale] Locale $locale): Response
    {
        Head::title($product->name->get($locale->value));

        return Inertia::render('Product/Pages/Edit', new EditProductPageData(
            product: ProductDetailData::fromModel($product, $locale),
            updateUrl: route('larasell.admin.products.update', $product),
            indexUrl: route('larasell.admin.products.index'),
            categories: $this->categories($locale),
            createCategoryUrl: config('larasell.admin.categories.enabled') ? route('larasell.admin.categories.create') : null,
            sections: $this->sections->data(SectionForm::Product, $product, new SectionContext($locale)),
        ));
    }

    /**
     * Changes the name in the content locale and keeps its other translations, through the
     * core's actions, which also move the product's automatic URLs.
     */
    public function update(ProductRequest $request, Product $product, #[CurrentContentLocale] Locale $locale): RedirectResponse
    {
        // With the sections' own fields, so a section that fails leaves the product unchanged.
        DB::transaction(function () use ($request, $product, $locale): void {
            $stock = $request->stock();
            // The product checks each quantity limit against the other one it has as it's set, so
            // a new range wholly above the old one sets its maximum first, and any other its minimum.
            $limits = $stock['min_quantity'] !== null && $product->max_quantity !== null && $stock['min_quantity'] > $product->max_quantity
                ? ['max_quantity' => $stock['max_quantity'], 'min_quantity' => $stock['min_quantity']]
                : ['min_quantity' => $stock['min_quantity'], 'max_quantity' => $stock['max_quantity']];

            $product = $this->updateProduct->handle(ProductId::of($product), new UpdateProductPayload([
                'name' => $product->name->with($locale->value, $request->validated('name')),
                'status' => $request->validated('status'),
                ...$request->hasDescription() ? ['description' => $this->description($request, $product, $locale)] : [],
                'weight' => $request->weight(),
                'dimensions' => $request->dimensions(),
                'sku' => $request->validated('sku'),
                'barcode' => $request->validated('barcode'),
                ...$limits,
                ...array_diff_key($stock, $limits),
            ], $request->attachments()));

            $this->saveRelations($request, $product);
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
     * The product's descriptions with the content locale's changed, and without it when it was emptied.
     */
    protected function description(ProductRequest $request, Product $product, Locale $locale): ?TranslatableRichText
    {
        $text = $request->description();

        if ($text === null) {
            return $product->description?->without($locale->value);
        }

        return $product->description?->with($locale->value, $text)
            ?? TranslatableRichText::of($text, $locale->value);
    }

    /**
     * Saves the product's categories.
     */
    protected function saveRelations(ProductRequest $request, Product $product): void
    {
        $this->setCategories->handle(ProductId::of($product), CategoryId::many($request->categoryIds()));
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
