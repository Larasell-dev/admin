<?php

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Http\Controllers\AdminController;
use Larasell\Admin\Http\Controllers\AvatarController;
use Larasell\Admin\Http\Controllers\CategoryController;
use Larasell\Admin\Http\Controllers\CategoryProductController;
use Larasell\Admin\Http\Controllers\CustomerController;
use Larasell\Admin\Http\Controllers\Dev\RouteController as DevRouteController;
use Larasell\Admin\Http\Controllers\Dev\SessionController as DevSessionController;
use Larasell\Admin\Http\Controllers\HideCategoryController;
use Larasell\Admin\Http\Controllers\HomeController;
use Larasell\Admin\Http\Controllers\LoginController;
use Larasell\Admin\Http\Controllers\LogoutController;
use Larasell\Admin\Http\Controllers\Media\FolderController;
use Larasell\Admin\Http\Controllers\Media\MediaController;
use Larasell\Admin\Http\Controllers\Media\ProductImageController;
use Larasell\Admin\Http\Controllers\MoveCategoryController;
use Larasell\Admin\Http\Controllers\ProfileController;
use Larasell\Admin\Http\Controllers\SearchProductsNotInCategoryController;
use Larasell\Admin\Http\Controllers\SettingsController;
use Larasell\Admin\Http\Controllers\TableActionController;
use Larasell\Admin\Http\Controllers\UnhideCategoryController;
use Larasell\Admin\Http\Middleware\Authenticate;
use Larasell\Admin\Http\Middleware\RedirectIfAuthenticated;
use Larasell\Admin\NavigationGroup;

Route::middleware(RedirectIfAuthenticated::class)->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login')->withHead(title: 'Sign in');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware(Authenticate::class)->group(function () {
    Route::get('/', HomeController::class)->name('home')->withHead(title: 'Dashboard');
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index')->withHead(title: 'Categories');
    Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('categories/create', [CategoryController::class, 'create'])->name('categories.create')->withHead(title: 'New category');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::get('categories/{category}/available-products', SearchProductsNotInCategoryController::class)->name('categories.available-products.index');
    Route::post('categories/{category}/products', [CategoryProductController::class, 'store'])->name('categories.products.store');
    Route::post('categories/{category}/hide', HideCategoryController::class)->name('categories.hide');
    Route::post('categories/{category}/unhide', UnhideCategoryController::class)->name('categories.unhide');
    Route::post('categories/{category}/move', MoveCategoryController::class)->name('categories.move');
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index')->withHead(title: 'Customers');

    if (config('larasell.media.enabled')) {
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::get('media/folders/{folder}', [FolderController::class, 'show'])->name('media.folders.show');
        Route::delete('media/folders/{folder}', [FolderController::class, 'destroy'])->name('media.folders.destroy');
        Route::post('media/product-images', [ProductImageController::class, 'store'])->name('media.product-images.store');
        Route::delete('media/product-images/{productImage}', [ProductImageController::class, 'destroy'])->name('media.product-images.destroy');
    }

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
Navigation::add(NavItem::Categories->label(), NavItem::Categories->value, icon: Icon::Folders)->position(NavItem::Categories->position());
Navigation::add(NavItem::Customers->label(), NavItem::Customers->value, icon: Icon::Group)->position(NavItem::Customers->position());

if (config('larasell.media.enabled')) {
    Navigation::add(NavItem::Media->label(), NavItem::Media->value, icon: Icon::Image)->position(NavItem::Media->position());
}

Navigation::add(NavItem::Settings->label(), NavItem::Settings->value, icon: Icon::Settings)
    ->position(NavItem::Settings->position())
    ->children(function (NavigationGroup $settings): void {
        $settings->add('Admins', 'larasell.admin.settings.admins.index', Icon::Team);
    });
