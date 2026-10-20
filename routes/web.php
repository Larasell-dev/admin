<?php

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Http\Controllers\AvatarController;
use Larasell\Admin\Http\Controllers\CustomerController;
use Larasell\Admin\Http\Controllers\Dev\RouteController as DevRouteController;
use Larasell\Admin\Http\Controllers\Dev\SessionController as DevSessionController;
use Larasell\Admin\Http\Controllers\HomeController;
use Larasell\Admin\Http\Controllers\LoginController;
use Larasell\Admin\Http\Controllers\LogoutController;
use Larasell\Admin\Http\Controllers\ProfileController;
use Larasell\Admin\Http\Controllers\TableActionController;
use Larasell\Admin\Http\Middleware\Authenticate;
use Larasell\Admin\Http\Middleware\RedirectIfAuthenticated;

Route::middleware(RedirectIfAuthenticated::class)->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware(Authenticate::class)->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/avatar', [AvatarController::class, 'store'])->middleware('throttle:10,1')->name('profile.avatar.store');
    Route::post('tables/{table}/{record}/actions/{action}', TableActionController::class)->name('tables.actions');
    Route::post('logout', LogoutController::class)->name('logout');
});

// Dev-only shortcuts. Never registered outside the local environment.
if (app()->environment('local')) {
    Route::prefix('__dev')->name('dev.')->group(function () {
        Route::get('/', [DevRouteController::class, 'index'])->name('index');
        Route::get('login', [DevSessionController::class, 'store'])->name('login');
        Route::get('logout', [DevSessionController::class, 'destroy'])->name('logout');
    });
}

Navigation::add('Home', 'home', icon: Icon::Home);
Navigation::add('Customers', 'larasell.admin.customers.index', icon: Icon::Group);
