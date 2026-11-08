<?php

namespace Larasell\Admin\Media\Data;

use Larasell\Larasell\Media\Models\MediaFolder;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One folder of the whole tree, for the breadcrumb. */
#[TypeScript]
class MediaFolderNodeData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        /** null at the top level. */
        public ?int $parentId,
        public string $url,
    ) {}

    public static function fromModel(MediaFolder $folder): self
    {
        return new self($folder->id, $folder->name, $folder->parent_id, route('larasell.admin.media.index', ['folder' => $folder->id]));
    }
}
