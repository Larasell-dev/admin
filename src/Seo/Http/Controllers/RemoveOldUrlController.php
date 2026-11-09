<?php

namespace Larasell\Admin\Seo\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Seo\Actions\RemoveOldUrl;
use Larasell\Admin\Seo\Http\Requests\RemoveOldUrlRequest;

class RemoveOldUrlController
{
    /** Removes an old URL of a product or category and goes back to its page. */
    public function __invoke(RemoveOldUrlRequest $request, RemoveOldUrl $remove): RedirectResponse
    {
        $remove->handle($request->seoUrl());

        return back();
    }
}
