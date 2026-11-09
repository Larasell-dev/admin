<?php

namespace Larasell\Admin\Team\Actions;

use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Platform\Auth\AvatarPath;
use Larasell\Admin\Team\Exceptions\AvatarNotStored;

class UploadAvatar
{
    /**
     * Stores the avatar in avatars/ on the panel disk, without assigning it to an admin. Only the
     * latest upload can be saved to a profile, so the pending one is deleted first.
     *
     * @throws AvatarNotStored
     */
    public function handle(UploadAvatarPayload $payload): AvatarPath
    {
        $disk = config('larasell.admin.panel.disk');

        if ($payload->pending !== null) {
            Storage::disk($disk)->delete($payload->pending->path);
        }

        $path = $payload->avatar->storePublicly(AvatarPath::DIRECTORY, ['disk' => $disk]);

        if ($path === false) {
            throw AvatarNotStored::onDisk();
        }

        return AvatarPath::fromString($path);
    }
}
