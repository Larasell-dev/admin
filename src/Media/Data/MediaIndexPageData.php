<?php

namespace Larasell\Admin\Media\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MediaIndexPageData extends Data
{
    public function __construct(
        /** @var array<int, MediaItemData> */
        public array $media,
        public PaginationData $pagination,
        public string $storeUrl,
    ) {}
}
