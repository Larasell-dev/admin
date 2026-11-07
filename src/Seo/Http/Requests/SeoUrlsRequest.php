<?php

namespace Larasell\Admin\Seo\Http\Requests;

use Larasell\Admin\Platform\Sections\SectionRequest;
use Larasell\Larasell\SalesChannels\SalesContext;
use Larasell\Larasell\Seo\Catalog\CustomUrls;
use Larasell\Larasell\Seo\SeoUrlManager;

/**
 * The URLs section of the product and category pages: a path per channel, posted as urls[{channel ID}][path].
 * Whether a path is free is only known while saving; the core throws CustomUrlRejected, which
 * SeoServiceProvider turns into the field's error.
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
     * The typed paths as the core's custom URLs, for the channels that serve the content locale;
     * null when the section posted none, e.g. with no storefront selling in the locale.
     */
    public function payload(): ?CustomUrls
    {
        $urls = $this->validated('urls');

        if (! is_array($urls)) {
            return null;
        }

        $locale = $this->context()->locale;
        $served = array_map(
            fn (SalesContext $context): int => $context->channelId->value,
            array_filter(app(SeoUrlManager::class)->contexts(), fn (SalesContext $context): bool => $context->locale === $locale),
        );
        $paths = [];

        foreach ($urls as $channelId => $url) {
            if (in_array((int) $channelId, $served, true)) {
                $path = is_array($url) && is_string($url['path'] ?? null) ? trim($url['path']) : '';
                $paths[(int) $channelId] = $path === '' ? null : $path;
            }
        }

        return CustomUrls::byChannel($locale, $paths);
    }
}
