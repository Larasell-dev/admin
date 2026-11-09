<?php

namespace Larasell\Admin\Media\Actions;

use Illuminate\Database\ConnectionInterface;
use Larasell\Larasell\Media\Actions\CreateMediaFolder;
use Larasell\Larasell\Media\Actions\CreateMediaFolderPayload;
use Larasell\Larasell\Media\Actions\MoveMedia;
use Larasell\Larasell\Media\Actions\MoveMediaPayload;
use Larasell\Larasell\Media\Exceptions\MediaFolderNameTaken;
use Larasell\Larasell\Media\Exceptions\MediaFolderNotFound;
use Larasell\Larasell\Media\MediaFolderId;
use Larasell\Larasell\Media\Models\MediaFolder;

/**
 * Creates a folder and moves a media item into it, for dropping an item onto the library's
 * "New folder" button. Both or neither: a folder is never left behind without the item.
 */
final readonly class MoveMediaToNewFolder
{
    public function __construct(private ConnectionInterface $db, private CreateMediaFolder $create, private MoveMedia $move) {}

    /**
     * @throws MediaFolderNotFound when the parent folder doesn't exist (any more)
     * @throws MediaFolderNameTaken when a folder in the same parent has the name
     */
    public function handle(MoveMediaToNewFolderPayload $payload): MediaFolder
    {
        return $this->db->transaction(function () use ($payload): MediaFolder {
            $folder = $this->create->handle(new CreateMediaFolderPayload($payload->name, $payload->parentId));
            $this->move->handle(new MoveMediaPayload([$payload->mediaId], MediaFolderId::of($folder)));

            return $folder;
        });
    }
}
