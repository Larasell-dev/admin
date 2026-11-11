<?php

namespace Larasell\Admin\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Larasell\Seo\SeoUrlId;

/** Making an old URL current again: its ID from the route. */
class RestoreOldUrlRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    public function seoUrl(): SeoUrlId
    {
        return SeoUrlId::fromString((string) $this->route('url'));
    }
}
