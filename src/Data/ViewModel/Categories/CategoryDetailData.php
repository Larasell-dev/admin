<?php

namespace Larasell\Admin\Data\ViewModel\Categories;

use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Category;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CategoryDetailData extends Data
{
    public function __construct(
        public int $id,
        /** In the content locale, null while the category has none in it. */
        public ?string $name,
        public ?string $slug,
        /** What the store shows in the content locale, the name of another locale while it has none. */
        public string $fallbackName,
        public string $fallbackSlug,
        public bool $visible,
    ) {}

    public static function fromModel(Category $category, Locale $locale): self
    {
        return new self(
            id: $category->id,
            name: $category->name->has($locale->value) ? $category->name->get($locale->value) : null,
            slug: $category->slug->has($locale->value) ? $category->slug->get($locale->value) : null,
            fallbackName: $category->name->get($locale->value),
            fallbackSlug: $category->slug->get($locale->value),
            visible: $category->status === Visibility::Visible,
        );
    }
}
