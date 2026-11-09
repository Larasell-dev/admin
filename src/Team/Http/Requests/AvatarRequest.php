<?php

namespace Larasell\Admin\Team\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Larasell\Admin\Platform\Auth\AvatarPath;
use Larasell\Admin\Team\Actions\UploadAvatarPayload;
use Larasell\Admin\Team\Http\Controllers\AvatarController;

class AvatarRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(2 * 1024)
                    ->rules(Rule::dimensions()->ratio(1)->minWidth(128)->maxWidth(2048)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Mostly files over PHP's upload_max_filesize, which it drops before the max rule runs.
            'avatar.uploaded' => __('The picture may be larger than the server accepts.'),
        ];
    }

    /**
     * A pending path outside avatars/ throws here, before anything is deleted.
     */
    public function payload(): UploadAvatarPayload
    {
        $pending = $this->session()->get(AvatarController::PENDING_SESSION_KEY);

        return new UploadAvatarPayload(
            avatar: $this->file('avatar'),
            pending: is_string($pending) ? AvatarPath::fromString($pending) : null,
        );
    }
}
