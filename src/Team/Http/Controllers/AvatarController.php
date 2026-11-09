<?php

namespace Larasell\Admin\Team\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Platform\Auth\AvatarPath;
use Larasell\Admin\Team\Exceptions\AvatarNotStored;
use Larasell\Admin\Team\Http\Requests\AvatarRequest;

class AvatarController
{
    /**
     * The session key holding the uploaded avatar that hasn't been saved to the profile yet.
     */
    public const PENDING_SESSION_KEY = 'larasell-admin.pending-avatar';

    public function store(AvatarRequest $request): JsonResponse
    {
        $disk = Storage::disk(config('larasell.admin.panel.disk'));

        // Only the latest upload can be saved, so an earlier unsaved one is no longer needed.
        $previous = $request->session()->pull(self::PENDING_SESSION_KEY);

        // A pending path outside avatars/ throws before anything is deleted.
        if (is_string($previous)) {
            $disk->delete(AvatarPath::fromString($previous)->path);
        }

        $path = $request->file('avatar')->storePublicly(AvatarPath::DIRECTORY, ['disk' => config('larasell.admin.panel.disk')]);

        if ($path === false) {
            throw AvatarNotStored::onDisk();
        }

        $request->session()->put(self::PENDING_SESSION_KEY, $path);

        return new JsonResponse(['path' => $path, 'url' => $disk->url($path)], 201);
    }
}
