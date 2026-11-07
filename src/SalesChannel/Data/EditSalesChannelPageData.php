<?php

namespace Larasell\Admin\SalesChannel\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class EditSalesChannelPageData extends Data
{
    public function __construct(
        public SalesChannelData $channel,
        public string $updateUrl,
        public string $destroyUrl,
        /** @var array<int, SalesChannelOptionData> */
        public array $locales,
        /** @var array<int, SalesChannelOptionData> */
        public array $currencies,
        /**
         * The storefront's domains by language, primary first; null for a channel of another type.
         *
         * @var array<int, EntryPointData>|null
         */
        public ?array $entryPoints,
        public ?string $addEntryPointUrl,
    ) {}
}
