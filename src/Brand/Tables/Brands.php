<?php

namespace Larasell\Admin\Brand\Tables;

use Illuminate\Database\Eloquent\Builder;
use Larasell\Admin\Platform\ContentLocale\Resolvers\ContentLocaleResolver;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Tables\Actions\TableAction;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Table;
use Larasell\Larasell\Catalog\Brands\Models\Brand;
use Larasell\Larasell\Translatable;

/** The catalog's brands, each with how many products it has. */
class Brands extends Table
{
    public function title(): string
    {
        return __('Brands');
    }

    /** @return Builder<Brand> */
    protected function model(): Builder
    {
        return Brand::query()->withCount('products')->orderBy('slug');
    }

    public function columns(): array
    {
        $locale = app(ContentLocaleResolver::class)->resolve(request())->value;

        return [
            TextColumn::make('name', __('Name'))->map(fn (Translatable $name): string => $name->get($locale)),
            TextColumn::make('slug', __('Slug')),
            TextColumn::make('products_count', __('Products')),
        ];
    }

    public function rowLink(Brand $brand): string
    {
        return route('larasell.admin.brands.show', $brand);
    }

    public function headerActions(): array
    {
        return [TableAction::make(Translate::later('Add brand'))->route('larasell.admin.brands.create')->icon(Icon::Add)];
    }

    public function emptyState(): array
    {
        return [
            'title' => __('No brands yet'),
            'description' => __('Brands are what products are marketed under, e.g. on their pages and in filters.'),
            'icon' => Icon::Award,
            'actions' => $this->headerActions(),
        ];
    }
}
