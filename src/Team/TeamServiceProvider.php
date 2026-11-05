<?php

namespace Larasell\Admin\Team;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Platform\Navigation\Facades\Navigation;
use Larasell\Admin\Platform\Navigation\NavItem;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Team\Http\Controllers\AvatarController;
use Larasell\Admin\Team\Http\Controllers\ProfileController;
use Larasell\Admin\Team\Http\Controllers\TeamController;

/**
 * The team module: the settings pages that list, add and edit the admins who can sign in, their
 * navigation item under Settings, and each admin's own profile. It can't be turned off, since
 * without it nobody could add an admin or change their password in the panel.
 */
class TeamServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Navigation::add('Team', 'larasell.admin.settings.team.index', Icon::Team)->under(NavItem::Settings);

        AdminPanel::resolve()->routes(function (): void {
            Route::get('settings/team', [TeamController::class, 'index'])->name('settings.team.index')->withHead(title: 'Team');
            Route::post('settings/team', [TeamController::class, 'store'])->name('settings.team.store');
            Route::get('settings/team/create', [TeamController::class, 'create'])->name('settings.team.create')->withHead(title: 'New member');
            Route::get('settings/team/{admin}/edit', [TeamController::class, 'edit'])->name('settings.team.edit');
            Route::put('settings/team/{admin}', [TeamController::class, 'update'])->name('settings.team.update');
            Route::get('profile', [ProfileController::class, 'edit'])->name('profile')->withHead(title: 'Profile');
            Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::post('profile/avatar', [AvatarController::class, 'store'])->middleware('throttle:10,1')->name('profile.avatar.store');
        });
    }
}
