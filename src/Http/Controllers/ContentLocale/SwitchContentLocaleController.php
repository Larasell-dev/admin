<?php

namespace Larasell\Admin\Http\Controllers\ContentLocale;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Actions\ContentLocale\SetContentLocale;
use Larasell\Admin\Http\Requests\ContentLocale\SetContentLocaleRequest;

class SwitchContentLocaleController
{
    /**
     * Edits content in the picked locale from now on, and reloads the page it was picked on.
     */
    public function __invoke(SetContentLocaleRequest $request, SetContentLocale $setContentLocale): RedirectResponse
    {
        $setContentLocale->handle($request->payload());

        return back();
    }
}
