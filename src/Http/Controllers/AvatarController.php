<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Http\Requests\AvatarRequest;
use RuntimeException;

class AvatarController
{
    /**
     * The session key holding the uploaded avatar that hasn't been saved to the profile yet.
     */
    public const PENDING_SESSION_KEY = 'larasell-admin.pending-avatar';

    public function store(AvatarRequest $request): JsonResponse
    {
        $disk = Storage::disk(config('larasell-admin.disk'));

        // Only the latest upload can be saved, so an earlier unsaved one is no longer needed.
        $previous = $request->session()->pull(self::PENDING_SESSION_KEY);

        if (is_string($previous)) {
            $disk->delete($previous);
        }

        $path = $request->file('avatar')->storePublicly('avatars', ['disk' => config('larasell-admin.disk')]);

        if ($path === false) {
            throw new RuntimeException('Unable to store the avatar.');
        }

        $request->session()->put(self::PENDING_SESSION_KEY, $path);

        return new JsonResponse(['path' => $path, 'url' => $disk->url($path)], 201);
    }
}
