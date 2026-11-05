<?php

namespace Larasell\Admin\Platform\ContentLocale\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Larasell\Admin\Platform\ContentLocale\Actions\SetContentLocale;
use Larasell\Admin\Platform\ContentLocale\Http\Requests\SetContentLocaleRequest;

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
