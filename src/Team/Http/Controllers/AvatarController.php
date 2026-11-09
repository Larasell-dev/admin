<?php

namespace Larasell\Admin\Team\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Team\Exceptions\AvatarNotStored;
use Larasell\Admin\Team\Http\Requests\AvatarRequest;

class AvatarController
{
    /**
     * The session key holding the uploaded avatar that hasn't been saved to the profile yet.
     */
    public const PENDING_SESSION_KEY = 'larasell-admin.pending-avatar';

    /**
     * Whether the path is a file directly in avatars/, the folder uploads are stored in. Only
     * those are deleted, so a path that was changed can't delete another file on the disk.
     */
    public static function isAvatar(string $path): bool
    {
        return preg_match('#^avatars/[A-Za-z0-9][A-Za-z0-9._-]*$#', $path) === 1;
    }

    public function store(AvatarRequest $request): JsonResponse
    {
        $disk = Storage::disk(config('larasell.admin.panel.disk'));

        // Only the latest upload can be saved, so an earlier unsaved one is no longer needed.
        $previous = $request->session()->pull(self::PENDING_SESSION_KEY);

        if (is_string($previous)) {
            $disk->delete($previous);
        }

        $path = $request->file('avatar')->storePublicly('avatars', ['disk' => config('larasell.admin.panel.disk')]);

        if ($path === false) {
            throw AvatarNotStored::onDisk();
        }

        $request->session()->put(self::PENDING_SESSION_KEY, $path);

        return new JsonResponse(['path' => $path, 'url' => $disk->url($path)], 201);
    }
}
