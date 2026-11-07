<?php

namespace Larasell\Admin\Seo\Actions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
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
     * @throws ValidationException when the confirmation doesn't match
     */
    public function handle(RemoveOldUrlPayload $payload): void
    {
        $old = SeoUrl::query()
            ->whereKey($payload->url->value)
            ->where('status', SeoUrlStatus::Historic->value)
            ->firstOrFail();
        $expected = __('Delete redirect');

        if ($payload->confirmation !== $expected) {
            throw ValidationException::withMessages([
                'confirmation' => __('Type ":text" to confirm.', ['text' => $expected]),
            ]);
        }

        $this->urls->remove($old);
    }
}
