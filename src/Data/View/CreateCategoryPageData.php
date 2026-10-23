<?php

namespace Larasell\Admin\Data\View;

use Larasell\Admin\Data\ViewModel\CategoryData;
use Larasell\Admin\Data\ViewModel\CategoryDetailData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateCategoryPageData extends Data
{
    public function __construct(
        /** @var array<int, CategoryData> */
        public array $categories,
        public string $createUrl,
        public string $storeUrl,
        public string $indexUrl,
        /** The category the new one goes into, or null for a root category. */
        public ?CategoryDetailData $parent,
    ) {}
}
