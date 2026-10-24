<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class SettingsController
{
    /**
     * Settings has no page of its own; its navigation item opens the first settings page.
     */
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('larasell.admin.settings.admins.index');
    }
}
