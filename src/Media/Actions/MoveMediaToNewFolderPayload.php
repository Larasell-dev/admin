<?php

namespace Larasell\Admin\Media\Actions;

use Larasell\Larasell\Media\MediaFolderId;
use Larasell\Larasell\Media\MediaFolderName;
use Larasell\Larasell\Media\MediaId;

/** A media item, and the new folder to put it in: its name and its parent (null for the top level). */
final readonly class MoveMediaToNewFolderPayload
{
    public function __construct(public MediaId $mediaId, public MediaFolderName $name, public ?MediaFolderId $parentId) {}
}
