<?php

namespace Larasell\Admin\Manufacturer\Tables;

use Illuminate\Database\Eloquent\Builder;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Tables\Actions\TableAction;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Table;
use Larasell\Larasell\Catalog\Manufacturers\Models\Manufacturer;
use Larasell\Larasell\Catalog\Manufacturers\PostalAddress;

/** The legal producers of the catalog's products, each with its country and how many products it makes. */
class Manufacturers extends Table
{
    public function title(): string
    {
        return __('Manufacturers');
    }

    /** @return Builder<Manufacturer> */
    protected function model(): Builder
    {
        return Manufacturer::query()->withCount('products')->orderBy('name');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name', __('Name')),
            TextColumn::make('address', __('Country'))->map(fn (PostalAddress $address): string => $address->country->label(app()->getLocale())),
            TextColumn::make('email', __('Email')),
            TextColumn::make('products_count', __('Products')),
        ];
    }

    public function rowLink(Manufacturer $manufacturer): string
    {
        return route('larasell.admin.manufacturers.show', $manufacturer);
    }

    public function headerActions(): array
    {
        return [TableAction::make(Translate::later('Add manufacturer'))->route('larasell.admin.manufacturers.create')->icon(Icon::Add)];
    }

    public function emptyState(): array
    {
        return [
            'title' => __('No manufacturers yet'),
            'description' => __('Product pages in the EU name the manufacturer, with its postal and email address (GPSR).'),
            'icon' => Icon::Building,
            'actions' => $this->headerActions(),
        ];
    }
}
