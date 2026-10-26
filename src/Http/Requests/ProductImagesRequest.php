<?php

namespace Larasell\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ProductImagesRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array'],
            // Raster images only: SVGs can carry scripts, and have no placeholder.
            'images.*' => ['required', File::types(['jpg', 'jpeg', 'png', 'webp', 'avif'])->max(10 * 1024)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'images.*.mimes' => 'Each image must be a JPG, PNG, WebP or AVIF file.',
            'images.*.max' => 'Each image must be 10 MB or smaller.',
        ];
    }
}
