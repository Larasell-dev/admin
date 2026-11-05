<?php

namespace Larasell\Admin\Product\Tables;

use Larasell\Admin\Platform\ContentLocale\ContentLocaleResolver;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Tables\Actions\Action;
use Larasell\Admin\Platform\Tables\Actions\TableAction;
use Larasell\Admin\Platform\Tables\Attributes\UseModel;
use Larasell\Admin\Platform\Tables\Columns\BadgeColumn;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Enums\Variant;
use Larasell\Admin\Platform\Tables\Table;
use Larasell\Admin\Product\Actions\DeleteProduct;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Translatable;

#[UseModel(Product::class)]
class Products extends Table
{
    public function columns(): array
    {
        // Named in the content locale picked on the product pages; this page has no select of its own.
        $locale = app(ContentLocaleResolver::class)->resolve(request())->value;

        return [
            TextColumn::make('name')->map(fn (Translatable $name): string => $name->get($locale)),
            // Null means the product's stock isn't tracked, unlike 0, which means it's sold out.
            TextColumn::make('stock')->map(fn (?int $stock): int|string => $stock ?? 'Not tracked'),
            BadgeColumn::make('status', 'Visibility')
                ->map(fn (Visibility $status): string => $status === Visibility::Visible ? 'Visible' : 'Hidden')
                ->variant([Visibility::Visible->value => Variant::Success]),
        ];
    }

    public function rowLink(Product $product): string
    {
        return route('larasell.admin.products.edit', $product);
    }

    public function headerActions(): array
    {
        return [
            TableAction::make('Add product')->route('larasell.admin.products.create')->icon(Icon::Add),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make('Delete', handle: fn (Product $product) => app(DeleteProduct::class)->handle($product))
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
            'actions' => $this->headerActions(),
        ];
    }
}
