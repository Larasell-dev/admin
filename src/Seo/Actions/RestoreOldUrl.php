<?php

namespace Larasell\Admin\Seo\Actions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Larasell\Larasell\SalesChannels\SalesChannelId;
use Larasell\Larasell\SalesChannels\SalesContext;
use Larasell\Larasell\Seo\Models\SeoUrl;
use Larasell\Larasell\Seo\SeoResources;
use Larasell\Larasell\Seo\SeoUrlManager;
use Larasell\Larasell\Seo\SeoUrlStatus;

class RestoreOldUrl
{
    public function __construct(
        private readonly SeoUrlManager $urls,
        private readonly SeoResources $resources,
    ) {}

    /**
     * Makes an old URL its page's current one in its channel and locale. It becomes the custom URL,
     * so renames keep it, unless it is what the name gives anyway; then the URL follows the name again.
     *
     * @throws ModelNotFoundException when it isn't an old URL of a page that still exists
     */
    public function handle(RestoreOldUrlPayload $payload): void
    {
        $old = SeoUrl::query()
            ->whereKey($payload->url->value)
            ->where('status', SeoUrlStatus::Historic->value)
            ->firstOrFail();
        $page = $this->resources->page($old->resource_type, $old->resource_id)
            ?? throw (new ModelNotFoundException)->setModel(SeoUrl::class, [$old->id]);
        $context = SalesContext::for(new SalesChannelId($old->sales_channel_id), $old->locale);

        if ($old->path === $this->resources->for($page)->path($page, $context)) {
            $this->urls->resetToAutomatic($page, $context);
        } else {
            $this->urls->setCustom($page, $context, $old->path);
        }
    }
}
