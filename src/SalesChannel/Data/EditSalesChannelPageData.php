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
        /** A page of the storefront's domains; null for a channel of another type. */
        public ?EntryPointsData $entryPoints,
        public ?string $addEntryPointUrl,
    ) {}
}
