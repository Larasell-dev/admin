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
use Larasell\Larasell\Catalog\AttributeScope;
use Larasell\Larasell\Catalog\Models\ProductAttribute;
use Larasell\Larasell\Translatable;

/** The catalog's attributes, each with how many products and variants have values of it. */
class Attributes extends Table
{
    public function title(): string
    {
        return __('Attributes');
    }

    /**
     * Adds products_count, the products with values of the attribute, and variants_count, the
     * variants with their own values of it (or their own "none").
     *
     * @param  Builder<ProductAttribute>  $query
     * @return Builder<ProductAttribute>
     */
    public static function withUsage(Builder $query): Builder
    {
        return $query->withCount('values')
            ->selectSub(DB::table('larasell_product_product_attribute_value')
                ->join('larasell_product_attribute_values', 'larasell_product_attribute_values.id', '=', 'larasell_product_product_attribute_value.product_attribute_value_id')
                ->whereColumn('larasell_product_attribute_values.product_attribute_id', 'larasell_product_attributes.id')
                ->selectRaw('count(distinct larasell_product_product_attribute_value.product_id)'), 'products_count')
            ->selectSub(DB::table('larasell_product_variant_attribute_values')
                ->whereColumn('product_attribute_id', 'larasell_product_attributes.id')
                ->selectRaw('count(distinct product_variant_id)'), 'variants_count');
    }

    /** @return Builder<ProductAttribute> */
    protected function model(): Builder
    {
        return self::withUsage(ProductAttribute::query());
    }

    public function columns(): array
    {
        $locale = app(ContentLocaleResolver::class)->resolve(request())->value;

        return [
            TextColumn::make('name', __('Name'))->map(fn (Translatable $name): string => $name->get($locale)),
            TextColumn::make('scope', __('Describes'))->map(fn (AttributeScope $scope): string => self::scopeLabel($scope)),
            TextColumn::make('values_count', __('Values')),
            TextColumn::make('products_count', __('Products')),
            TextColumn::make('variants_count', __('Variants')),
        ];
    }

    public static function scopeLabel(AttributeScope $scope): string
    {
        return match ($scope) {
            AttributeScope::Product => __('Products only'),
            AttributeScope::Inherited => __('Products, variants can override'),
            AttributeScope::Variant => __('Variants only'),
        };
    }

    public function rowLink(ProductAttribute $attribute): string
    {
        return route('larasell.admin.attributes.show', $attribute);
    }

    public function headerActions(): array
    {
        return [TableAction::make(Translate::later('Add attribute'))->route('larasell.admin.attributes.create')->icon(Icon::Add)];
    }

    public function emptyState(): array
    {
        return [
            'title' => __('No attributes yet'),
            'description' => __('Attributes describe products and variants, e.g. their material or care.'),
            'icon' => Icon::PriceTag3,
            'actions' => $this->headerActions(),
        ];
    }
}
