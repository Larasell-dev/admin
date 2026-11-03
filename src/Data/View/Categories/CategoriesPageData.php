<?php

namespace Larasell\Admin\Data\View\Categories;

use Larasell\Admin\Data\ViewModel\Categories\CategoryData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CategoriesPageData extends Data
{
    public function __construct(
        /** @var array<int, CategoryData> */
        public array $categories,
        public string $createUrl,
    ) {}
}
