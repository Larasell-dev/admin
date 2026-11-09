<?php

namespace Larasell\Admin\Seo\Actions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Larasell\Admin\Seo\SeoUrlId;
use Larasell\Larasell\Seo\Models\SeoUrl;
use Larasell\Larasell\Seo\SeoUrlManager;
use Larasell\Larasell\Seo\SeoUrlStatus;

class RemoveOldUrl
{
    public function __construct(private readonly SeoUrlManager $urls) {}

    /**
     * Frees an old URL of a page that still exists, so it no longer redirects and another page can
     * take it. It can't be undone, so the admin types "Delete redirect" in their language first.
     *
     * @throws ModelNotFoundException when it isn't an old URL, e.g. a current one or a deleted page's
     */
    public function handle(SeoUrlId $url): void
    {
        $old = SeoUrl::query()
            ->whereKey($url->value)
            ->where('status', SeoUrlStatus::Historic->value)
            ->firstOrFail();
        $this->urls->remove($old);
    }
}
