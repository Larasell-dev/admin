<?php

namespace Larasell\Admin\Category\Data;

use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CategoryDetailData extends Data
{
    public function __construct(
        public int $id,
        /** In the content locale, null while the category has none in it. */
        public ?string $name,
        /** What the store shows in the content locale, the name of another locale while it has none. */
        public string $fallbackName,
        public bool $visible,
    ) {}

    public static function fromModel(Category $category, Locale $locale): self
    {
        return new self(
            id: $category->id,
            name: $category->name->has($locale->value) ? $category->name->get($locale->value) : null,
            fallbackName: $category->name->get($locale->value),
            visible: $category->status === Visibility::Visible,
        );
    }
}
