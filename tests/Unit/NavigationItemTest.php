<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\ValueObjects\NavigationItem;

beforeEach(function () {
    Route::get('/admin/home', fn () => '')->name('larasell.admin.home');
});

it('constructs a navigation item', function () {
    $item = new NavigationItem('Home', 'larasell.admin.home');

    expect($item->label)->toBe('Home')
        ->and($item->route)->toBe('larasell.admin.home');
});

it('converts a navigation item to an array with the resolved url', function () {
    $item = new NavigationItem('Home', 'larasell.admin.home');

    expect($item->toArray())->toBe([
        'label' => 'Home',
        'url' => route('larasell.admin.home'),
    ]);
});

it('throws an exception when the label is empty', function () {
    new NavigationItem('  ', 'larasell.admin.home');
})->throws(InvalidArgumentException::class, 'Navigation item label cannot be empty.');

it('throws an exception when the route is empty', function () {
    new NavigationItem('Home', '');
})->throws(InvalidArgumentException::class, 'Navigation item [Home] route cannot be empty.');
