<?php

namespace Larasell\Admin\Seo\Data;

use Illuminate\Database\Eloquent\Model;
use Larasell\Admin\Platform\Sections\Props;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\Seo\SeoResources;
use Larasell\Larasell\Seo\SeoUrlManager;
use Larasell\Larasell\Storefront\StorefrontContext;
use Larasell\Larasell\Storefront\Storefronts;

/**
 * The URL of the product or category in each storefront channel that sells in the content
 * locale, through the channel's primary domain for it. A new one has no URLs yet.
 */
class SeoUrlsProps implements Props
{
    public function __construct(
        private Storefronts $storefronts,
        private SeoUrlManager $urls,
        private SeoResources $resources,
    ) {}

    public function resolve(?Model $resource, SectionContext $context): SeoUrlsData
    {
        $storefronts = array_values(array_filter(
            $this->storefronts->contexts(),
            fn (StorefrontContext $storefront): bool => $storefront->sales->locale === $context->locale,
        ));
        $names = SalesChannel::query()
            ->whereKey(array_map(fn (StorefrontContext $storefront): int => $storefront->sales->channelId->value, $storefronts))
            ->pluck('name', 'id');

        $urls = array_map(function (StorefrontContext $storefront) use ($resource, $names): SeoUrlData {
            $url = $resource !== null ? $this->urls->canonical($resource, $storefront->sales) : null;

            return new SeoUrlData(
                channelId: $storefront->sales->channelId->value,
                channel: (string) $names->get($storefront->sales->channelId->value, $storefront->sales->channel),
                baseUrl: rtrim($storefront->url(), '/').'/',
                customPath: $url?->is_custom ? $url->path : null,
                // The current automatic path, which may carry a suffix that sets it apart from
                // another page's; what the name would give while a custom path replaces it.
                automaticPath: match (true) {
                    $resource === null => null,
                    $url !== null && ! $url->is_custom => $url->path,
                    default => $this->resources->for($resource)->path($resource, $storefront->sales),
                },
            );
        }, $storefronts);

        usort($urls, fn (SeoUrlData $a, SeoUrlData $b): int => [$a->channel, $a->channelId] <=> [$b->channel, $b->channelId]);

        return new SeoUrlsData($urls);
    }
}
