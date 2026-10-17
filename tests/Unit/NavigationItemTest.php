<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Exceptions\InvalidNavigationItem;
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
        'id' => 'larasell.admin.home',
        'label' => 'Home',
        'url' => route('larasell.admin.home'),
    ]);
});

it('throws an exception when the label is empty', function () {
    new NavigationItem('  ', 'larasell.admin.home');
})->throws(InvalidNavigationItem::class, 'Navigation item label cannot be empty.');

it('throws an exception when the route is empty', function () {
    new NavigationItem('Home', '');
})->throws(InvalidNavigationItem::class, 'Navigation item [Home] route cannot be empty.');

it('accepts positions at both limits', function (int $position) {
    $item = (new NavigationItem('Home', 'larasell.admin.home'))->position($position);

    expect($item->position)->toBe($position);
})->with([0, 1_000_000]);

it('throws when the position is out of range', function (int $position) {
    (new NavigationItem('Home', 'larasell.admin.home'))->position($position);
})->with([-1, 1_000_001])->throws(InvalidNavigationItem::class, 'must be between 0 and 1000000');

it('throws when an item is placed twice', function (Closure $first, Closure $second) {
    $item = new NavigationItem('Home', 'larasell.admin.home');

    $first($item);
    $second($item);
})->with([
    'position then before' => [fn ($i) => $i->position(1), fn ($i) => $i->before('larasell.admin.notes')],
    'before then after' => [fn ($i) => $i->before('larasell.admin.notes'), fn ($i) => $i->after('larasell.admin.notes')],
    'after then position' => [fn ($i) => $i->after('larasell.admin.notes'), fn ($i) => $i->position(1)],
    'position twice' => [fn ($i) => $i->position(1), fn ($i) => $i->position(2)],
])->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.home] is already placed.');

it('throws when an item targets itself', function () {
    (new NavigationItem('Home', 'larasell.admin.home'))->before('larasell.admin.home');
})->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.home] cannot be placed relative to itself.');
