<?php

namespace Larasell\Admin\Category\Data;

use Illuminate\Support\Collection;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A category to put a product into, outside the categories module. It works while the categories
 * are turned off, when there is no category page to link to.
 */
#[TypeScript]
class CategoryOptionData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $visible,
        /** The category's page, null while the categories are turned off. */
        public ?string $url,
        /** @var array<int, CategoryOptionData> */
        public array $children,
    ) {}

    /**
     * Nests the categories under their parents, starting at the root categories, named in the locale.
     *
     * @param  Collection<int, Category>  $categories
     * @return array<int, CategoryOptionData>
     */
    public static function tree(Collection $categories, Locale $locale): array
    {
        return self::branch($categories->groupBy('parent_id'), null, (bool) config('larasell.admin.categories.enabled'), $locale);
    }

    /**
     * @param  Collection<array-key, Collection<int, Category>>  $byParent
     * @return array<int, CategoryOptionData>
     */
    protected static function branch(Collection $byParent, ?int $parentId, bool $linked, Locale $locale): array
    {
        // groupBy() keys categories without a parent by an empty string.
        return $byParent->get($parentId ?? '', collect())
            ->map(fn (Category $category): self => new self(
                id: $category->id,
                name: $category->name->get($locale->value),
                visible: $category->status === Visibility::Visible,
                url: $linked ? route('larasell.admin.categories.show', $category) : null,
                children: self::branch($byParent, $category->id, $linked, $locale),
            ))
            ->values()
            ->all();
    }
}
