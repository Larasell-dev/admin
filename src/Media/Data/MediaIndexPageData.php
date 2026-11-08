<?php

namespace Larasell\Admin\Media\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MediaIndexPageData extends Data
{
    public function __construct(
        /** The folder shown; null for the top level. */
        public ?int $folderId,
        /** The folders in it, by name. */
        /** @var array<int, MediaFolderData> */
        public array $folders,
        /** Every folder, for the breadcrumb. */
        /** @var array<int, MediaFolderNodeData> */
        public array $tree,
        /** @var array<int, MediaItemData> */
        public array $media,
        public PaginationData $pagination,
        /** Uploads into the folder shown. */
        public string $storeUrl,
        public string $rootUrl,
        public string $createFolderUrl,
    ) {}
}
