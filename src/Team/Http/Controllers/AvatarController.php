<?php

namespace Larasell\Admin\Team\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Team\Actions\UploadAvatar;
use Larasell\Admin\Team\Http\Requests\AvatarRequest;

class AvatarController
{
    /**
     * The session key holding the uploaded avatar that hasn't been saved to the profile yet.
     */
    public const PENDING_SESSION_KEY = 'larasell-admin.pending-avatar';

    public function store(AvatarRequest $request, UploadAvatar $uploadAvatar): JsonResponse
    {
        $path = $uploadAvatar->handle($request->payload());

        $request->session()->put(self::PENDING_SESSION_KEY, $path->path);

        return new JsonResponse([
            'path' => $path->path,
            'url' => Storage::disk(config('larasell.admin.panel.disk'))->url($path->path),
        ], 201);
    }
}
