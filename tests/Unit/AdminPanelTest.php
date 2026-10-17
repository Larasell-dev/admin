<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\NavIcon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Exceptions\InvalidNavigationItem;

beforeEach(function () {
    Route::get('/admin/home', fn () => '')->name('larasell.admin.home');
    Route::get('/admin/settings', fn () => '')->name('larasell.admin.settings');
    Route::get('/admin/notes', fn () => '')->name('larasell.admin.notes');
});

it('resolves the singleton instance from the container', function () {
    expect(AdminPanel::resolve())->toBe(app(AdminPanel::class))
        ->toBeInstanceOf(AdminPanel::class);
});

it('registers navigation items and prefixes short route names', function () {
    $panel = new AdminPanel;

    $panel->add('Home', 'home', NavIcon::Home);
    $panel->add('Settings', 'larasell.admin.settings', 'settings-3');

    expect($panel->navigationItems())->toBe([
        ['id' => 'larasell.admin.home', 'label' => 'Home', 'url' => route('larasell.admin.home'), 'icon' => 'Home'],
        ['id' => 'larasell.admin.settings', 'label' => 'Settings', 'url' => route('larasell.admin.settings'), 'icon' => 'Settings3'],
    ]);
});

it('registers scripts and page paths', function () {
    $panel = new AdminPanel;

    $panel->script('resources/js/admin/app.tsx');
    $panel->pagePath('/path/to/pages');

    expect($panel->scripts())->toBe(['resources/js/admin/app.tsx'])
        ->and($panel->pagePaths())->toBe(['/path/to/pages']);
});

it('appends navigation items in registration order by default', function () {
    $panel = new AdminPanel;

    $panel->add('Settings', 'larasell.admin.settings', 'settings-3');
    $panel->add('Home', 'larasell.admin.home', 'home');

    expect(labels($panel))->toBe(['Settings', 'Home']);
});

it('sorts navigation items by position in ascending order', function () {
    $panel = new AdminPanel;

    $panel->add('Home', 'larasell.admin.home', 'home')->position(500);
    $panel->add('Settings', 'larasell.admin.settings', 'settings-3')->position(100);
    $panel->add('Notes', 'larasell.admin.notes', 'sticky-note')->position(300);

    expect(labels($panel))->toBe(['Settings', 'Notes', 'Home']);
});

it('puts unpositioned items below positioned ones', function () {
    $panel = new AdminPanel;

    $panel->add('Notes', 'larasell.admin.notes', 'sticky-note');
    $panel->add('Home', 'larasell.admin.home', 'home')->position(900);
    $panel->add('Settings', 'larasell.admin.settings', 'settings-3');

    expect(labels($panel))->toBe(['Home', 'Notes', 'Settings']);
});

it('sorts items with the same position alphabetically by label', function () {
    $panel = new AdminPanel;

    $panel->add('Settings', 'larasell.admin.settings', 'settings-3')->position(100);
    $panel->add('Notes', 'larasell.admin.notes', 'sticky-note')->position(100);
    $panel->add('Home', 'larasell.admin.home', 'home')->position(100);

    expect(labels($panel))->toBe(['Home', 'Notes', 'Settings']);
});

it('places a navigation item before another item by id', function () {
    $panel = new AdminPanel;

    $panel->add('Home', 'larasell.admin.home', 'home');
    $panel->add('Settings', 'larasell.admin.settings', 'settings-3');
    $panel->add('Notes', 'larasell.admin.notes', 'sticky-note')->before('larasell.admin.settings');

    expect(labels($panel))->toBe(['Home', 'Notes', 'Settings']);
});

it('places a navigation item after another item by enum case', function () {
    $panel = new AdminPanel;

    $panel->add('Home', 'larasell.admin.home', 'home');
    $panel->add('Settings', 'larasell.admin.settings', 'settings-3');
    $panel->add('Notes', 'larasell.admin.notes', 'sticky-note')->after(NavItem::Home);

    expect(labels($panel))->toBe(['Home', 'Notes', 'Settings']);
});

it('resolves targets registered after the item that refers to them', function () {
    $panel = new AdminPanel;

    $panel->add('Notes', 'larasell.admin.notes', 'sticky-note')->after('larasell.admin.settings');
    $panel->add('Settings', 'larasell.admin.settings', 'settings-3')->after('larasell.admin.home');
    $panel->add('Home', 'larasell.admin.home', 'home');

    expect(labels($panel))->toBe(['Home', 'Settings', 'Notes']);
});

it('throws when a navigation id is registered twice', function () {
    $panel = new AdminPanel;

    $panel->add('Home', 'larasell.admin.home', 'home');
    $panel->add('Start', 'home', 'home');
})->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.home] is already registered.');

it('throws when the relative target does not exist', function () {
    $panel = new AdminPanel;

    $panel->add('Home', 'larasell.admin.home', 'home');
    $panel->add('Notes', 'larasell.admin.notes', 'sticky-note')->before('larasell.admin.missing');

    $panel->navigationItems();
})->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.notes] targets unknown item [larasell.admin.missing].');

it('throws when relative placements form a cycle', function () {
    $panel = new AdminPanel;

    $panel->add('Home', 'larasell.admin.home', 'home')->before('larasell.admin.settings');
    $panel->add('Settings', 'larasell.admin.settings', 'settings-3')->before('larasell.admin.home');

    $panel->navigationItems();
})->throws(InvalidNavigationItem::class, 'Navigation items [larasell.admin.home, larasell.admin.settings] form a placement cycle.');

it('has a registered navigation item for every NavItem case', function () {
    $ids = array_map(fn (NavItem $case): string => $case->value, NavItem::cases());

    foreach ($ids as $id) {
        expect(Route::has($id))->toBeTrue();
    }

    expect(array_column(AdminPanel::resolve()->navigationItems(), 'id'))->toContain(...$ids);
});

function labels(AdminPanel $panel): array
{
    return array_column($panel->navigationItems(), 'label');
}
