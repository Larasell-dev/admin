<?php

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Platform\Auth\Http\Controllers\LoginController;
use Larasell\Admin\Platform\Auth\Http\Controllers\LogoutController;
use Larasell\Admin\Platform\Auth\Http\Middleware\Authenticate;
use Larasell\Admin\Platform\Auth\Http\Middleware\RedirectIfAuthenticated;
use Larasell\Admin\Platform\ContentLocale\Http\Controllers\SwitchContentLocaleController;
use Larasell\Admin\Platform\Navigation\Facades\Navigation;
use Larasell\Admin\Platform\Navigation\NavItem;
use Larasell\Admin\Platform\Panel\Http\Controllers\Dev\RouteController as DevRouteController;
use Larasell\Admin\Platform\Panel\Http\Controllers\Dev\SessionController as DevSessionController;
use Larasell\Admin\Platform\Panel\Http\Controllers\HomeController;
use Larasell\Admin\Platform\Panel\Http\Controllers\SettingsController;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Tables\Http\Controllers\TableActionController;

Route::middleware(RedirectIfAuthenticated::class)->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login')->withHead(title: 'Sign in');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware(Authenticate::class)->group(function () {
    Route::get('/', HomeController::class)->name('home')->withHead(title: 'Dashboard');
    Route::get('settings', SettingsController::class)->name('settings');
    Route::post('tables/{table}/{record}/actions/{action}', TableActionController::class)->name('tables.actions');
    Route::put('content-locale', SwitchContentLocaleController::class)->name('content-locale.update');
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
Navigation::add(NavItem::Settings->label(), NavItem::Settings->value, icon: Icon::Settings)->position(NavItem::Settings->position());
