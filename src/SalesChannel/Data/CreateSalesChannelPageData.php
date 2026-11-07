<?php

namespace Larasell\Admin\SalesChannel\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CreateSalesChannelPageData extends Data
{
    public function __construct(
        public string $storeUrl,
        /** @var string[] The page only shows a choice when there are several. */
        public array $types,
        /** @var array<int, SalesChannelOptionData> */
        public array $locales,
        /** @var array<int, SalesChannelOptionData> */
        public array $currencies,
    ) {}
}
