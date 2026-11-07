<?php

namespace Larasell\Admin\Seo\Http\Requests;

use Larasell\Admin\Platform\Sections\SectionRequest;
use Larasell\Admin\Seo\Actions\ProductUrlsPayload;

/**
 * The URLs section of the product page: a path per channel, posted as urls[{channel ID}][path].
 * Whether a path is free is only known while saving; SaveProductUrls reports that.
 */
class ProductUrlsRequest extends SectionRequest
{
    /** The section's key on the product page. */
    public const SECTION = 'seo';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'urls' => ['array'],
            'urls.*' => ['array:path'],
            'urls.*.path' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['urls.*.path' => __('URL')];
    }

    /**
     * Null when the section posted no URLs, e.g. with no storefront selling in the locale.
     */
    public function payload(): ?ProductUrlsPayload
    {
        $urls = $this->validated('urls');

        if (! is_array($urls)) {
            return null;
        }

        $paths = [];

        foreach ($urls as $channelId => $url) {
            $path = is_array($url) && is_string($url['path'] ?? null) ? trim($url['path']) : '';
            $paths[(int) $channelId] = $path === '' ? null : $path;
        }

        return new ProductUrlsPayload($paths);
    }
}
