<?php

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Http\Controllers\HomeController;
use Larasell\Admin\Http\Controllers\LoginController;
use Larasell\Admin\Http\Controllers\LogoutController;
use Larasell\Admin\Http\Middleware\Authenticate;
use Larasell\Admin\Http\Middleware\RedirectIfAuthenticated;

Route::middleware(RedirectIfAuthenticated::class)->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware(Authenticate::class)->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::post('logout', LogoutController::class)->name('logout');
});
