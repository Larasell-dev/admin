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
}
