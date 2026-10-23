<?php

namespace Larasell\Admin\Data\View;

use Larasell\Admin\Data\ViewModel\CategoryData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The category page for a category that does not exist (anymore). Its null category tells it
 * apart from CategoryPageData, so the page can narrow its props to either one.
 */
#[TypeScript]
class MissingCategoryPageData extends Data
{
    public function __construct(
        /** @var array<int, CategoryData> */
        public array $categories,
        public string $createUrl,
        #[LiteralTypeScriptType('null')]
        public null $category = null,
    ) {}
}
