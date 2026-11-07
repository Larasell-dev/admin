<?php

namespace Larasell\Admin\Platform\InterfaceLocale\Http\Controllers;

use Inertia\Inertia;
use Larasell\Admin\Platform\InterfaceLocale\Http\Requests\SetInterfaceLocaleRequest;
use Larasell\Admin\Platform\InterfaceLocale\Resolvers\InterfaceLocaleResolver;
use Symfony\Component\HttpFoundation\Response;

class SwitchInterfaceLocaleController
{
    /**
     * Shows the panel in the picked language from now on. Reloads the page it was picked on in
     * full, so the frontend loads that language's translations too.
     */
    public function __invoke(SetInterfaceLocaleRequest $request, InterfaceLocaleResolver $resolver): Response
    {
        $resolver->remember($request, $request->interfaceLocale());

        return Inertia::location(url()->previous());
    }
}
