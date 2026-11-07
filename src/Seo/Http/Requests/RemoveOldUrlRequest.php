<?php

namespace Larasell\Admin\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Admin\Seo\Actions\RemoveOldUrlPayload;
use Larasell\Admin\Seo\SeoUrlId;

/** Removing an old URL: its ID from the route and the confirmation the admin typed. */
class RemoveOldUrlRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ['confirmation' => ['nullable', 'string']];
    }

    public function payload(): RemoveOldUrlPayload
    {
        return new RemoveOldUrlPayload(
            url: SeoUrlId::fromString((string) $this->route('url')),
            confirmation: $this->filled('confirmation') ? $this->string('confirmation')->value() : null,
        );
    }
}
