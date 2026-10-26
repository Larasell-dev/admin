<?php

namespace Larasell\Admin\Tables\Panel;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Attributes\Remember;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Translatable;

/**
 * The products in a category, on the category's page.
 */
class CategoryProducts extends Table
{
    public function __construct(
        #[Remember] public Category $category,
    ) {}

    /**
     * @return BelongsToMany<Product, Category>
     */
    protected function model(): BelongsToMany
    {
        return $this->category->products();
    }

    public function title(): string
    {
        return 'Products';
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name')->map(fn (Translatable $name): string => $name->get()),
            TextColumn::make('status', 'Visibility')
                ->map(fn (Visibility $status): string => $status === Visibility::Visible ? 'Visible' : 'Hidden'),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make('View')
                ->route('larasell.admin.products.edit')
                ->icon(Icon::Eye)
                ->hidden(! config('larasell.admin.products.enabled')),
            Action::make('Remove', handle: fn (Product $product) => $this->category->products()->detach($product))
                ->confirm(
                    title: 'Remove product',
                    message: 'The product is only removed from this category. It stays in the store and its other categories.',
                )
                // Not a bin: the product isn't deleted, only taken out of this category.
                ->icon(Icon::FolderReduce)
                ->destructive(),
        ];
    }

    public function emptyState(): array
    {
        return [
            'title' => 'No products yet',
            'description' => 'Products in this category show up here.',
        ];
    }
}
