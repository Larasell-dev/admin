<?php

namespace Larasell\Admin\Data\ViewModel;

use Illuminate\Support\Collection;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Category;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $visible,
        public string $url,
        public string $hideUrl,
        public string $unhideUrl,
        public string $deleteUrl,
        public string $createChildUrl,
        public string $moveUrl,
        /** @var array<int, CategoryData> */
        public array $children,
    ) {}

    /**
     * Nests the categories under their parents, starting at the root categories.
     *
     * @param  Collection<int, Category>  $categories
     * @return array<int, CategoryData>
     */
    public static function tree(Collection $categories): array
    {
        return self::branch($categories->groupBy('parent_id'), null);
    }

    /**
     * @param  Collection<array-key, Collection<int, Category>>  $byParent
     * @return array<int, CategoryData>
     */
    protected static function branch(Collection $byParent, ?int $parentId): array
    {
        // groupBy() keys categories without a parent by an empty string.
        return $byParent->get($parentId ?? '', collect())
            ->map(fn (Category $category): self => new self(
                id: $category->id,
                name: $category->name->get(),
                visible: $category->status === Visibility::Visible,
                url: route('larasell.admin.categories.show', $category),
                hideUrl: route('larasell.admin.categories.hide', $category),
                unhideUrl: route('larasell.admin.categories.unhide', $category),
                deleteUrl: route('larasell.admin.categories.destroy', $category),
                createChildUrl: route('larasell.admin.categories.create', ['parent' => $category->id]),
                moveUrl: route('larasell.admin.categories.move', $category),
                children: self::branch($byParent, $category->id),
            ))
            ->values()
            ->all();
    }
}
