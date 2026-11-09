<?php

namespace Larasell\Admin\Team\Actions;

use Illuminate\Http\UploadedFile;
use Larasell\Admin\Platform\Auth\AvatarPath;

/** An uploaded avatar, with the earlier upload of the same session that wasn't saved yet. */
final readonly class UploadAvatarPayload
{
    public function __construct(
        public UploadedFile $avatar,
        public ?AvatarPath $pending,
    ) {}
}
