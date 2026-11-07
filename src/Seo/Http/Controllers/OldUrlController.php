<?php

namespace Larasell\Admin\Seo\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Larasell\Larasell\SalesChannels\SalesChannelId;
use Larasell\Larasell\SalesChannels\SalesContext;
use Larasell\Larasell\Seo\Models\SeoUrl;
use Larasell\Larasell\Seo\SeoResources;
use Larasell\Larasell\Seo\SeoUrlManager;
use Larasell\Larasell\Seo\SeoUrlStatus;

/**
 * The old URLs of a product or category, which redirect to its current one. Only old URLs of
 * pages that still exist: a current URL can't be removed, and a deleted page's URLs aren't shown.
 */
class OldUrlController
{
    public function __construct(
        private SeoUrlManager $urls,
        private SeoResources $resources,
    ) {}

    /**
     * Frees the old URL, so it no longer redirects and another page can take it. It can't be undone,
     * so the admin types the confirmation text first, like a table action's.
     */
    public function destroy(Request $request, int $url): RedirectResponse
    {
        $old = $this->old($url);
        $expected = __('Delete redirect');

        if ($request->input('confirmation') !== $expected) {
            throw ValidationException::withMessages([
                'confirmation' => __('Type ":text" to confirm.', ['text' => $expected]),
            ]);
        }

        $this->urls->remove($old);

        return back();
    }

    /**
     * Makes the old URL the page's current one in its channel. It stays through renames, unless it
     * is what the name gives anyway.
     */
    public function restore(int $url): RedirectResponse
    {
        $old = $this->old($url);
        $page = $this->resources->page($old->resource_type, $old->resource_id) ?? abort(404);
        $context = SalesContext::for(new SalesChannelId($old->sales_channel_id), $old->locale);

        $old->path === $this->resources->for($page)->path($page, $context)
            ? $this->urls->resetToAutomatic($page, $context)
            : $this->urls->setCustom($page, $context, $old->path);

        return back();
    }

    private function old(int $id): SeoUrl
    {
        return SeoUrl::query()->whereKey($id)->where('status', SeoUrlStatus::Historic->value)->firstOrFail();
    }
}
