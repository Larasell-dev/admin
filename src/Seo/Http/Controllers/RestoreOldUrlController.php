<?php

namespace Larasell\Admin\Seo\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Admin\Seo\Http\Requests\RestoreOldUrlRequest;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Seo\Actions\RestoreOldUrl;

class RestoreOldUrlController
{
    /** Makes an old URL of a product or category current again and goes back to its page. */
    public function __invoke(RestoreOldUrlRequest $request, RestoreOldUrl $restore, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $restore->handle($request->seoUrl(), $origin);

        return back();
    }
}
