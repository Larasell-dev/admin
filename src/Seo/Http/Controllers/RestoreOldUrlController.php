<?php

namespace Larasell\Admin\Seo\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Seo\Actions\RestoreOldUrl;
use Larasell\Admin\Seo\Http\Requests\RestoreOldUrlRequest;

class RestoreOldUrlController
{
    /** Makes an old URL of a product or category current again and goes back to its page. */
    public function __invoke(RestoreOldUrlRequest $request, RestoreOldUrl $restore): RedirectResponse
    {
        $restore->handle($request->payload());

        return back();
    }
}
