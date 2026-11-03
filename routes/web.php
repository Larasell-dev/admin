<?php

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Http\Controllers\AdminController;
use Larasell\Admin\Http\Controllers\AvatarController;
use Larasell\Admin\Http\Controllers\Dev\RouteController as DevRouteController;
use Larasell\Admin\Http\Controllers\Dev\SessionController as DevSessionController;
use Larasell\Admin\Http\Controllers\HomeController;
use Larasell\Admin\Http\Controllers\LoginController;
use Larasell\Admin\Http\Controllers\LogoutController;
use Larasell\Admin\Http\Controllers\ProfileController;
use Larasell\Admin\Http\Controllers\SettingsController;
use Larasell\Admin\Http\Controllers\TableActionController;
use Larasell\Admin\Http\Middleware\Authenticate;
use Larasell\Admin\Http\Middleware\RedirectIfAuthenticated;
use Larasell\Admin\NavigationGroup;

Route::middleware(RedirectIfAuthenticated::class)->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login')->withHead(title: 'Sign in');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware(Authenticate::class)->group(function () {
    Route::get('/', HomeController::class)->name('home')->withHead(title: 'Dashboard');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile')->withHead(title: 'Profile');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('settings', SettingsController::class)->name('settings');
    Route::get('settings/admins', [AdminController::class, 'index'])->name('settings.admins.index')->withHead(title: 'Admins');
    Route::post('settings/admins', [AdminController::class, 'store'])->name('settings.admins.store');
    Route::get('settings/admins/create', [AdminController::class, 'create'])->name('settings.admins.create')->withHead(title: 'New admin');
    Route::get('settings/admins/{admin}/edit', [AdminController::class, 'edit'])->name('settings.admins.edit');
    Route::put('settings/admins/{admin}', [AdminController::class, 'update'])->name('settings.admins.update');
    Route::post('profile/avatar', [AvatarController::class, 'store'])->middleware('throttle:10,1')->name('profile.avatar.store');
    Route::post('tables/{table}/{record}/actions/{action}', TableActionController::class)->name('tables.actions');
    Route::post('logout', LogoutController::class)->name('logout');
});

Route::any('{fallbackPlaceholder}', fn () => abort(404))->where('fallbackPlaceholder', '.*')->fallback()->name('not-found');

// Dev-only shortcuts. Never registered outside the local environment.
if (app()->environment('local')) {
    Route::prefix('__dev')->name('dev.')->group(function () {
        Route::get('/', [DevRouteController::class, 'index'])->name('index');
        Route::get('login', [DevSessionController::class, 'store'])->name('login');
        Route::get('logout', [DevSessionController::class, 'destroy'])->name('logout');
    });
}

Navigation::add(NavItem::Home->label(), NavItem::Home->value, icon: Icon::Home)->position(NavItem::Home->position());
Navigation::add(NavItem::Settings->label(), NavItem::Settings->value, icon: Icon::Settings)
    ->position(NavItem::Settings->position())
    ->children(function (NavigationGroup $settings): void {
        $settings->add('Admins', 'larasell.admin.settings.admins.index', Icon::Team);
    });
