<?php

namespace Larasell\Admin\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;
use Larasell\Admin\Seo\SeoUrlId;

/** Removing an old URL, once the admin typed "Delete redirect" in their language. */
class RemoveOldUrlRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ['confirmation' => [new Confirmation(__('Delete redirect'))]];
    }

    public function seoUrl(): SeoUrlId
    {
        return SeoUrlId::fromString((string) $this->route('url'));
    }
}
