<?php

namespace Larasell\Admin\Category\Data;

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
