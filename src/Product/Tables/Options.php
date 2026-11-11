<?php

namespace Larasell\Admin\Product\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\ContentLocale\Resolvers\ContentLocaleResolver;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Tables\Actions\TableAction;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Table;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinition;
use Larasell\Larasell\Catalog\Options\OptionDisplay;
use Larasell\Larasell\Translatable;

/** The catalog's options, each with how many products offer it and how many variants use it. */
class Options extends Table
{
    public function title(): string
    {
        return __('Options');
    }

    /**
     * Adds values_count, products_count, the products that offer the option, and variants_count,
     * the variants with a value of it.
     *
     * @param  Builder<OptionDefinition>  $query
     * @return Builder<OptionDefinition>
     */
    public static function withUsage(Builder $query): Builder
    {
        return $query->select('larasell_option_definitions.*')->withCount('values')
            ->selectSub(DB::table('larasell_product_options')
                ->whereColumn('option_definition_id', 'larasell_option_definitions.id')
                ->selectRaw('count(distinct product_id)'), 'products_count')
            ->selectSub(DB::table('larasell_product_variant_option_values as vov')
                ->join('larasell_product_options as po', 'po.id', '=', 'vov.product_option_id')
                ->whereColumn('po.option_definition_id', 'larasell_option_definitions.id')
                ->selectRaw('count(distinct vov.product_variant_id)'), 'variants_count');
    }

    /** @return Builder<OptionDefinition> */
    protected function model(): Builder
    {
        return self::withUsage(OptionDefinition::query());
    }

    public function columns(): array
    {
        $locale = app(ContentLocaleResolver::class)->resolve(request())->value;

        return [
            TextColumn::make('name', __('Name'))->map(fn (Translatable $name): string => $name->get($locale)),
            TextColumn::make('display', __('Display'))->map(fn (string $display): string => self::displayLabel(OptionDisplay::from($display))),
            TextColumn::make('values_count', __('Values')),
            TextColumn::make('products_count', __('Products')),
            TextColumn::make('variants_count', __('Variants')),
        ];
    }

    public static function displayLabel(OptionDisplay $display): string
    {
        return match ($display) {
            OptionDisplay::Dropdown => __('Dropdown'),
            OptionDisplay::Buttons => __('Buttons'),
            OptionDisplay::Swatch => __('Swatches'),
        };
    }

    public function rowLink(OptionDefinition $option): string
    {
        return route('larasell.admin.options.show', $option);
    }

    public function headerActions(): array
    {
        return [TableAction::make(Translate::later('Add option'))->route('larasell.admin.options.create')->icon(Icon::Add)];
    }

    public function emptyState(): array
    {
        return [
            'title' => __('No options yet'),
            'description' => __('Options tell a product\'s variants apart, e.g. their colour or size.'),
            'icon' => Icon::ListCheck,
            'actions' => $this->headerActions(),
        ];
    }
}
