<?php

namespace Larasell\Admin\Product\Tables;

use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\Table\Variant;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Attributes\UseModel;
use Larasell\Admin\Tables\Columns\BadgeColumn;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Translatable;

#[UseModel(Product::class)]
class Products extends Table
{
    public function columns(): array
    {
        return [
            TextColumn::make('name')->map(fn (Translatable $name): string => $name->get()),
            // Null means the product's stock isn't tracked, unlike 0, which means it's sold out.
            TextColumn::make('stock')->map(fn (?int $stock): int|string => $stock ?? 'Not tracked'),
            BadgeColumn::make('status', 'Visibility')
                ->map(fn (Visibility $status): string => $status === Visibility::Visible ? 'Visible' : 'Hidden')
                ->variant([Visibility::Visible->value => Variant::Success]),
        ];
    }

    public function actions(): array
    {
        return [
            // Variants, category links and cart items go with it; orders keep their own copy of the product.
            Action::make('Delete', handle: fn (Product $product) => $product->delete())
                ->confirm(message: 'It is removed from the store and every cart. Past orders keep it. This can\'t be undone.')
                ->icon(Icon::DeleteBin)
                ->destructive(),
        ];
    }

    public function emptyState(): array
    {
        return [
            'title' => 'No products yet',
            'description' => 'Products you add to the store show up here.',
            'icon' => Icon::ShoppingBag,
        ];
    }
}
