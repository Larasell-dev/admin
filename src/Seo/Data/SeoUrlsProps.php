<?php

namespace Larasell\Admin\Seo\Data;

use Illuminate\Database\Eloquent\Model;
use Larasell\Admin\Platform\Sections\Props;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Larasell\Larasell\Seo\Models\SeoUrl;
use Larasell\Larasell\Seo\SeoResources;
use Larasell\Larasell\Seo\SeoUrlManager;
use Larasell\Larasell\Seo\SeoUrlStatus;
use Larasell\Larasell\Storefront\StorefrontContext;
use Larasell\Larasell\Storefront\Storefronts;

/**
 * The URL of the product or category in each storefront channel that sells in the content
 * locale, through the channel's primary domain for it. A new one has no URLs yet.
 */
class SeoUrlsProps implements Props
{
    /** Old URLs per page in each channel's list, the first by default. */
    public const PAGE_SIZES = [10, 25, 50, 100];

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

        $urls = array_map(function (StorefrontContext $storefront) use ($resource, $names, $context): SeoUrlData {
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
                history: $this->history($resource, $storefront->sales->channelId->value, $context),
            );
        }, $storefronts);

        usort($urls, fn (SeoUrlData $a, SeoUrlData $b): int => [$a->channel, $a->channelId] <=> [$b->channel, $b->channelId]);

        return new SeoUrlsData($urls);
    }

    /**
     * The page of the channel's old URLs that old-urls[{channel ID}][page] and [size] ask for; the
     * last one when they ask for one past the end, e.g. after the last URL on it was removed.
     */
    private function history(?Model $resource, int $channelId, SectionContext $context): OldUrlsData
    {
        if ($resource === null) {
            return new OldUrlsData([], 1, self::PAGE_SIZES[0], 0);
        }

        $query = SeoUrl::query()
            ->where('resource_type', $this->resources->for($resource)->key())
            ->where('resource_id', $resource->getKey())
            ->where('sales_channel_id', $channelId)
            ->where('locale', $context->locale->value)
            ->where('status', SeoUrlStatus::Historic->value);

        $total = (clone $query)->count();
        $requested = request()->query('old-urls');
        $requested = is_array($requested) && is_array($requested[$channelId] ?? null) ? $requested[$channelId] : [];
        $size = in_array((int) ($requested['size'] ?? 0), self::PAGE_SIZES, true) ? (int) $requested['size'] : self::PAGE_SIZES[0];
        $page = max(1, min((int) ($requested['page'] ?? 1), (int) ceil($total / $size)));

        $urls = $query->orderByDesc('updated_at')->orderByDesc('id')
            ->forPage($page, $size)
            ->get()
            ->map(fn (SeoUrl $old): OldUrlData => new OldUrlData(
                id: $old->id,
                path: $old->path,
                removeUrl: route('larasell.admin.seo-urls.destroy', $old->id),
                restoreUrl: route('larasell.admin.seo-urls.restore', $old->id),
            ))
            ->all();

        return new OldUrlsData($urls, $page, $size, $total);
    }
}
