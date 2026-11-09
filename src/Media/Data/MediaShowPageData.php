<?php

namespace Larasell\Admin\Media\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MediaShowPageData extends Data
{
    public function __construct(
        public MediaDetailData $media,
        public string $updateUrl,
        public string $destroyUrl,
        /** Where closing the item goes. */
        public string $indexUrl,
    ) {}
}
