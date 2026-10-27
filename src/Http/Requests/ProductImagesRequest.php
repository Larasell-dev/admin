<?php

namespace Larasell\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

/**
 * One image per request, so a failure can't leave some of a request's images stored.
 */
class ProductImagesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Raster images only: SVGs can carry scripts, and have no placeholder.
            'image' => ['required', File::types(['jpg', 'jpeg', 'png', 'webp', 'avif'])->max(10 * 1024)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.mimes' => 'The image must be a JPG, PNG, WebP or AVIF file.',
            'image.max' => 'The image must be 10 MB or smaller.',
            // Mostly files over PHP's upload_max_filesize, which it drops before the max rule runs.
            'image.uploaded' => 'The image may be larger than the server accepts.',
        ];
    }
}
