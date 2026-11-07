<?php

namespace Larasell\Admin\Seo\Http\Requests;

use Larasell\Admin\Platform\Sections\SectionRequest;
use Larasell\Admin\Seo\Actions\SeoUrlsPayload;

/**
 * The URLs section of the product and category pages: a path per channel, posted as urls[{channel ID}][path].
 * Whether a path is free is only known while saving; SaveSeoUrls reports that.
 */
class SeoUrlsRequest extends SectionRequest
{
    /** The section's key on the product and category pages. */
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
    public function payload(): ?SeoUrlsPayload
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

        return new SeoUrlsPayload($paths);
    }
}
