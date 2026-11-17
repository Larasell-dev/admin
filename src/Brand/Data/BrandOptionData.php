<?php

namespace Larasell\Admin\Brand\Data;

use Larasell\Larasell\Catalog\Brands\Models\Brand;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A brand to pick, e.g. on the product form, named in the content locale. */
#[TypeScript]
class BrandOptionData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        /** Unique, so the product form can offer it instead of adding a brand with its slug. */
        public string $slug,
        /**
         * Its fields in the content locale as its form names them, to change it on the product form.
         *
         * @var array<string, string>
         */
        public array $fields,
        /** How many products it's on, which a change on the product form changes too. */
        public int $products,
    ) {}

    /** @return array<int, self> Every brand, by name. */
    public static function options(Locale $locale): array
    {
        return Brand::query()->withCount('products')->get()
            ->map(fn (Brand $brand): self => new self($brand->id, $brand->name->get($locale->value), $brand->slug, [
                'name' => $brand->name->has($locale->value) ? $brand->name->get($locale->value) : '',
                'slug' => $brand->slug,
                'description' => $brand->description?->has($locale->value) ? $brand->description->get($locale->value) : '',
            ], (int) $brand->products_count))
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
