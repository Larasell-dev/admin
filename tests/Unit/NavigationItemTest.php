<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidNavigationItem;
use Larasell\Admin\ValueObjects\NavigationItem;

beforeEach(function () {
    Route::get('/admin/home', fn () => '')->name('larasell.admin.home');
});

it('constructs a navigation item', function () {
    $item = new NavigationItem('Home', 'larasell.admin.home', Icon::Dashboard);

    expect($item->label)->toBe('Home')
        ->and($item->route)->toBe('larasell.admin.home')
        ->and($item->icon)->toBe(Icon::Dashboard);
});

it('resolves an icon passed as its Remix name', function (string $name, string $case) {
    $item = new NavigationItem('Home', 'larasell.admin.home', $name);

    expect($item->icon)->toBe(constant(Icon::class.'::'.$case));
})->with([
    'single word' => ['dashboard', 'Dashboard'],
    'with a number' => ['file-list-3', 'FileList3'],
    'leading digit' => ['24-hours', 'TwentyFourHours'],
    'one style only' => ['kanban-view', 'KanbanView'],
]);

it('converts a navigation item to an array with the resolved url and icon', function () {
    $item = new NavigationItem('Home', 'larasell.admin.home', '24-hours');

    expect($item->toData()->toArray())->toBe([
        'id' => 'larasell.admin.home',
        'label' => 'Home',
        'url' => route('larasell.admin.home'),
        'icon' => 'TwentyFourHours',
        'children' => [],
    ]);
});

it('throws when the icon is empty', function (string $icon) {
    new NavigationItem('Home', 'larasell.admin.home', $icon);
})->with(['', ' '])->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.home] icon cannot be empty.');

it('throws when the icon names a style variant', function (string $icon) {
    new NavigationItem('Home', 'larasell.admin.home', $icon);
})->with(['home-line', 'home-fill'])->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.home] icon must not end in -line or -fill.');

it('throws when the icon is not a Remix icon', function (string $icon) {
    new NavigationItem('Home', 'larasell.admin.home', $icon);
})->with(['not-an-icon', 'Home', 'HomeFill'])->throws(InvalidNavigationItem::class, 'is not a Remix icon');

it('throws an exception when the label is empty', function () {
    new NavigationItem('  ', 'larasell.admin.home', 'home');
})->throws(InvalidNavigationItem::class, 'Navigation item label cannot be empty.');

it('throws an exception when the route is empty', function () {
    new NavigationItem('Home', '', 'home');
})->throws(InvalidNavigationItem::class, 'Navigation item [Home] route cannot be empty.');

it('accepts positions at both limits', function (int $position) {
    $item = (new NavigationItem('Home', 'larasell.admin.home', 'home'))->position($position);

    expect($item->fixedPosition())->toBe($position);
})->with([0, 1_000_000]);

it('throws when the position is out of range', function (int $position) {
    (new NavigationItem('Home', 'larasell.admin.home', 'home'))->position($position);
})->with([-1, 1_000_001])->throws(InvalidNavigationItem::class, 'must be between 0 and 1000000');

it('throws when an item is placed twice', function (Closure $first, Closure $second) {
    $item = new NavigationItem('Home', 'larasell.admin.home', 'home');

    $first($item);
    $second($item);
})->with([
    'position then before' => [fn ($i) => $i->position(1), fn ($i) => $i->before('larasell.admin.notes')],
    'before then after' => [fn ($i) => $i->before('larasell.admin.notes'), fn ($i) => $i->after('larasell.admin.notes')],
    'after then position' => [fn ($i) => $i->after('larasell.admin.notes'), fn ($i) => $i->position(1)],
    'position twice' => [fn ($i) => $i->position(1), fn ($i) => $i->position(2)],
])->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.home] is already placed.');

it('throws when an item targets itself', function () {
    (new NavigationItem('Home', 'larasell.admin.home', 'home'))->before('larasell.admin.home');
})->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.home] cannot be placed relative to itself.');

it('keeps its state out of reach of outside writes', function (Closure $write) {
    $item = new NavigationItem('Home', 'larasell.admin.home', 'home');

    expect(fn () => $write($item))->toThrow(Error::class);
})->with([
    'label' => [fn ($item) => $item->label = 'Start'],
    'route' => [fn ($item) => $item->route = 'larasell.admin.start'],
    'icon' => [fn ($item) => $item->icon = Icon::Group],
    'position' => [fn ($item) => $item->position = 5],
    'before' => [fn ($item) => $item->before = 'larasell.admin.notes'],
    'after' => [fn ($item) => $item->after = 'larasell.admin.notes'],
    'parent' => [fn ($item) => $item->parent = 'larasell.admin.home'],
]);
