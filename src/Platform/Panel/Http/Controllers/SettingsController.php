<?php

namespace Larasell\Admin\Platform\Panel\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class SettingsController
{
    /**
     * The settings index: a card for each settings page. The pages come from the shared
     * settingsPages prop, which the command menu reads too.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Platform/Panel/Pages/Settings');
    }
}
