<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\AdminPanel;

beforeEach(function () {
    Route::get('/admin/home', fn () => '')->name('larasell.admin.home');
    Route::get('/admin/settings', fn () => '')->name('larasell.admin.settings');
});

it('resolves the singleton instance from the container', function () {
    expect(AdminPanel::resolve())->toBe(app(AdminPanel::class))
        ->toBeInstanceOf(AdminPanel::class);
});

it('registers navigation items and prefixes short route names', function () {
    $panel = new AdminPanel;

    $panel->add('Home', 'home');
    $panel->add('Settings', 'larasell.admin.settings');

    expect($panel->navigationItems())->toBe([
        ['label' => 'Home', 'url' => route('larasell.admin.home')],
        ['label' => 'Settings', 'url' => route('larasell.admin.settings')],
    ]);
});

it('registers scripts and page paths', function () {
    $panel = new AdminPanel;

    $panel->script('resources/js/admin/app.tsx');
    $panel->pagePath('/path/to/pages');

    expect($panel->scripts())->toBe(['resources/js/admin/app.tsx'])
        ->and($panel->pagePaths())->toBe(['/path/to/pages']);
});
