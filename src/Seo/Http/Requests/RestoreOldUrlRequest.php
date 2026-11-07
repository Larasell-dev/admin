<?php

namespace Larasell\Admin\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Admin\Seo\Actions\RestoreOldUrlPayload;
use Larasell\Admin\Seo\SeoUrlId;

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

    public function payload(): RestoreOldUrlPayload
    {
        return new RestoreOldUrlPayload(SeoUrlId::fromString((string) $this->route('url')));
    }
}
