<?php

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Http\Controllers\AvatarController;
use Larasell\Admin\Http\Controllers\HomeController;
use Larasell\Admin\Http\Controllers\LoginController;
use Larasell\Admin\Http\Controllers\LogoutController;
use Larasell\Admin\Http\Controllers\ProfileController;
use Larasell\Admin\Http\Middleware\Authenticate;
use Larasell\Admin\Http\Middleware\RedirectIfAuthenticated;

Route::middleware(RedirectIfAuthenticated::class)->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware(Authenticate::class)->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/avatar', [AvatarController::class, 'store'])->middleware('throttle:10,1')->name('profile.avatar.store');
    Route::post('logout', LogoutController::class)->name('logout');
});

Navigation::add('Home', 'home', icon: 'home');
