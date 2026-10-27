<?php

namespace Larasell\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

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
            'avatar.uploaded' => 'The picture may be larger than the server accepts.',
        ];
    }
}
