<?php

namespace Larasell\Admin\Media\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** The library's page; its entries come as a scroll prop, a page at a time, besides these. */
#[TypeScript]
class MediaIndexPageData extends Data
{
    public function __construct(
        /** The folder shown; null for the top level. */
        public ?int $folderId,
        /** Every folder, for the breadcrumb. */
        /** @var array<int, MediaFolderNodeData> */
        public array $tree,
        /** Uploads into the folder shown. */
        public string $storeUrl,
        public string $rootUrl,
        public string $createFolderUrl,
    ) {}
}
