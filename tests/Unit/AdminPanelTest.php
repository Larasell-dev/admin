<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\Icon;
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

    $panel->add('Home', 'home', Icon::Home);
    $panel->add('Settings', 'larasell.admin.settings', 'settings-3');

    expect($panel->navigationItems())->toBe([
        ['id' => 'larasell.admin.home', 'label' => 'Home', 'url' => route('larasell.admin.home'), 'icon' => 'Home', 'children' => []],
        ['id' => 'larasell.admin.settings', 'label' => 'Settings', 'url' => route('larasell.admin.settings'), 'icon' => 'Settings3', 'children' => []],
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

it('positions NavItem cases in steps of 1000 in declaration order', function () {
    expect(array_map(fn (NavItem $case): int => $case->position(), NavItem::cases()))
        ->toBe([1000, 2000, 3000]);
});

it('lists NavItem labels keyed by route', function () {
    expect(NavItem::all())->toBe([
        'larasell.admin.home' => 'Home',
        'larasell.admin.categories.index' => 'Categories',
        'larasell.admin.customers.index' => 'Customers',
    ]);
});

it('places built-in navigation items at their NavItem position', function () {
    AdminPanel::resolve()->add('Notes', 'larasell.admin.notes', 'sticky-note')->position(1500);

    expect(array_column(AdminPanel::resolve()->navigationItems(), 'id'))->toBe([
        'larasell.admin.home',
        'larasell.admin.notes',
        'larasell.admin.categories.index',
        'larasell.admin.customers.index',
    ]);
});

describe('nested navigation', function () {
    beforeEach(function () {
        Route::get('/admin/settings/taxes', fn () => '')->name('larasell.admin.taxes');
        Route::get('/admin/settings/shipping', fn () => '')->name('larasell.admin.shipping');
        Route::get('/admin/settings/payments', fn () => '')->name('larasell.admin.payments');
    });

    it('nests an item under its parent and serializes children', function () {
        $panel = new AdminPanel;

        $panel->add('Home', 'home', 'home');
        $panel->add('Settings', 'settings', 'settings-3');
        $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');

        expect($panel->navigationItems())->toBe([
            ['id' => 'larasell.admin.home', 'label' => 'Home', 'url' => route('larasell.admin.home'), 'icon' => 'Home', 'children' => []],
            ['id' => 'larasell.admin.settings', 'label' => 'Settings', 'url' => route('larasell.admin.settings'), 'icon' => 'Settings3', 'children' => [
                ['id' => 'larasell.admin.taxes', 'label' => 'Taxes', 'url' => route('larasell.admin.taxes'), 'icon' => 'Percent', 'children' => []],
            ]],
        ]);
    });

    it('defines children on the parent and keeps the parent chainable', function () {
        $panel = new AdminPanel;

        $panel->add('Home', 'home', 'home');
        $panel->add('Settings', 'settings', 'settings-3')
            ->children(function ($children) {
                $children->add('Taxes', 'taxes', 'percent');
                $children->add('Shipping', 'larasell.admin.shipping', 'truck');
            })
            ->before('larasell.admin.home');

        expect(tree($panel))->toBe([
            'Settings' => ['Taxes', 'Shipping'],
            'Home' => [],
        ]);
    });

    it('nests under a parent given as a NavItem case', function () {
        $panel = new AdminPanel;

        $panel->add('Home', NavItem::Home->value, 'home');
        $panel->add('Notes', 'notes', 'sticky-note')->under(NavItem::Home);

        expect(tree($panel))->toBe(['Home' => ['Notes']]);
    });

    it('nests under a parent registered later', function () {
        $panel = new AdminPanel;

        $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');
        $panel->add('Settings', 'settings', 'settings-3');

        expect(tree($panel))->toBe(['Settings' => ['Taxes']]);
    });

    it('merges children from children() and under() into one ordered group', function () {
        $panel = new AdminPanel;

        $panel->add('Settings', 'settings', 'settings-3')->children(function ($children) {
            $children->add('Taxes', 'taxes', 'percent')->position(2000);
            $children->add('Payments', 'payments', 'bank-card')->position(1000);
        });
        // A plugin adds to the group without access to the parent object.
        $panel->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->position(1500);

        expect(tree($panel))->toBe(['Settings' => ['Payments', 'Shipping', 'Taxes']]);
    });

    it('orders children by their own positions, independent of top-level positions', function () {
        $panel = new AdminPanel;

        $panel->add('Home', 'home', 'home')->position(500);
        $panel->add('Settings', 'settings', 'settings-3')->position(600);
        // 1000 would sort after both top-level items if positions were shared.
        $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->position(1000);
        $panel->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->position(0);

        expect(tree($panel))->toBe([
            'Home' => [],
            'Settings' => ['Shipping', 'Taxes'],
        ]);
    });

    it('accepts under() before or after position()', function () {
        $panel = new AdminPanel;

        $panel->add('Settings', 'settings', 'settings-3');
        $panel->add('Taxes', 'taxes', 'percent')->position(20)->under('larasell.admin.settings');
        $panel->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->position(10);

        expect(tree($panel))->toBe(['Settings' => ['Shipping', 'Taxes']]);
    });

    it('puts unpositioned children after positioned ones in registration order', function () {
        $panel = new AdminPanel;

        $panel->add('Settings', 'settings', 'settings-3');
        $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');
        $panel->add('Payments', 'payments', 'bank-card')->under('larasell.admin.settings');
        $panel->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->position(5);

        expect(tree($panel))->toBe(['Settings' => ['Shipping', 'Taxes', 'Payments']]);
    });

    it('sorts children with the same position alphabetically by label', function () {
        $panel = new AdminPanel;

        $panel->add('Settings', 'settings', 'settings-3');
        $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->position(1);
        $panel->add('Payments', 'payments', 'bank-card')->under('larasell.admin.settings')->position(1);

        expect(tree($panel))->toBe(['Settings' => ['Payments', 'Taxes']]);
    });

    it('places a child before or after a sibling', function () {
        $panel = new AdminPanel;

        $panel->add('Settings', 'settings', 'settings-3');
        $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');
        $panel->add('Payments', 'payments', 'bank-card')->under('larasell.admin.settings')->before('larasell.admin.taxes');
        $panel->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->after('larasell.admin.payments');

        expect(tree($panel))->toBe(['Settings' => ['Payments', 'Shipping', 'Taxes']]);
    });

    it('moves children along when the parent is placed relatively', function () {
        $panel = new AdminPanel;

        $panel->add('Home', 'home', 'home');
        $panel->add('Notes', 'notes', 'sticky-note');
        $panel->add('Settings', 'settings', 'settings-3')->before('larasell.admin.notes');
        $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');

        expect(tree($panel))->toBe([
            'Home' => [],
            'Settings' => ['Taxes'],
            'Notes' => [],
        ]);
    });

    it('throws when a child targets an item outside its group', function (Closure $register, string $message) {
        $panel = new AdminPanel;

        $panel->add('Home', 'home', 'home');
        $panel->add('Notes', 'notes', 'sticky-note');
        $panel->add('Settings', 'settings', 'settings-3');
        $panel->add('Payments', 'payments', 'bank-card')->under('larasell.admin.home');
        $register($panel);

        expect(fn () => $panel->navigationItems())->toThrow(InvalidNavigationItem::class, $message);
    })->with([
        'child targets a top-level item' => [
            fn ($panel) => $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->after('larasell.admin.home'),
            'Navigation item [larasell.admin.taxes] cannot be placed relative to [larasell.admin.home], which is not in the same group.',
        ],
        'top-level item targets a child' => [
            fn ($panel) => $panel->add('Taxes', 'taxes', 'percent')->before('larasell.admin.payments'),
            'Navigation item [larasell.admin.taxes] cannot be placed relative to [larasell.admin.payments], which is not in the same group.',
        ],
        'child targets a child of another parent' => [
            fn ($panel) => $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->after('larasell.admin.payments'),
            'Navigation item [larasell.admin.taxes] cannot be placed relative to [larasell.admin.payments], which is not in the same group.',
        ],
        'child targets its own parent' => [
            fn ($panel) => $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->before('larasell.admin.settings'),
            'Navigation item [larasell.admin.taxes] cannot be placed relative to [larasell.admin.settings], which is not in the same group.',
        ],
    ]);

    it('throws when nesting goes deeper than one level', function (Closure $register) {
        $panel = new AdminPanel;

        $register($panel);

        $panel->navigationItems();
    })->with([
        'grandchild registered last' => [function ($panel) {
            $panel->add('Settings', 'settings', 'settings-3');
            $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');
            $panel->add('Shipping', 'shipping', 'truck')->under('larasell.admin.taxes');
        }],
        'grandchild registered first' => [function ($panel) {
            $panel->add('Shipping', 'shipping', 'truck')->under('larasell.admin.taxes');
            $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');
            $panel->add('Settings', 'settings', 'settings-3');
        }],
        'children() inside children()' => [function ($panel) {
            $panel->add('Settings', 'settings', 'settings-3')->children(function ($children) {
                $children->add('Taxes', 'taxes', 'percent')->children(function ($grandchildren) {
                    $grandchildren->add('Shipping', 'shipping', 'truck');
                });
            });
        }],
        'children() on an item placed under() another' => [function ($panel) {
            $panel->add('Settings', 'settings', 'settings-3');
            $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->children(function ($children) {
                $children->add('Shipping', 'shipping', 'truck');
            });
        }],
    ])->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.shipping] is nested under [larasell.admin.taxes], which is itself nested.');

    it('throws when the parent does not exist', function () {
        $panel = new AdminPanel;

        $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.missing');

        $panel->navigationItems();
    })->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.taxes] is nested under unknown item [larasell.admin.missing].');

    it('throws when an item is nested under itself', function () {
        (new AdminPanel)->add('Taxes', 'taxes', 'percent')->under('larasell.admin.taxes');
    })->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.taxes] cannot be nested under itself.');

    it('throws when an item is nested twice', function () {
        $panel = new AdminPanel;

        $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->under('larasell.admin.home');
    })->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.taxes] is already nested under [larasell.admin.settings].');

    it('throws when children() re-nests an item that is already nested elsewhere', function () {
        $panel = new AdminPanel;

        $panel->add('Settings', 'settings', 'settings-3')->children(function ($children) {
            $children->add('Taxes', 'taxes', 'percent')->under('larasell.admin.home');
        });
    })->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.taxes] is already nested under [larasell.admin.settings].');

    it('throws when a child reuses an id from another level', function (Closure $register) {
        $register(new AdminPanel);
    })->with([
        'child after top-level' => [function ($panel) {
            $panel->add('Settings', 'settings', 'settings-3');
            $panel->add('Taxes', 'settings', 'percent')->under('larasell.admin.home');
        }],
        'child defined with children()' => [function ($panel) {
            $panel->add('Settings', 'settings', 'settings-3')->children(function ($children) {
                $children->add('Settings again', 'settings', 'percent');
            });
        }],
    ])->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.settings] is already registered.');

    it('throws on a placement cycle among children and names only those', function () {
        $panel = new AdminPanel;

        $panel->add('Settings', 'settings', 'settings-3');
        $panel->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->before('larasell.admin.shipping');
        $panel->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->before('larasell.admin.taxes');
        $panel->add('Payments', 'payments', 'bank-card')->under('larasell.admin.settings');

        $panel->navigationItems();
    })->throws(InvalidNavigationItem::class, 'Navigation items [larasell.admin.shipping, larasell.admin.taxes] form a placement cycle.');

    it('counts children toward the navigation limit', function () {
        $panel = new AdminPanel;

        $panel->add('Settings', 'settings', 'settings-3');
        for ($i = 1; $i < AdminPanel::NAVIGATION_MAX; $i++) {
            $panel->add("Child {$i}", "larasell.admin.child-{$i}", 'percent')->under('larasell.admin.settings');
        }

        $panel->add('One too many', 'larasell.admin.overflow', 'percent');
    })->throws(LogicException::class, 'Too many navigation items.');
});

function labels(AdminPanel $panel): array
{
    return array_column($panel->navigationItems(), 'label');
}

/**
 * The navigation as top-level label => list of child labels, in order.
 *
 * @return array<string, list<string>>
 */
function tree(AdminPanel $panel): array
{
    $tree = [];
    foreach ($panel->navigationItems() as $item) {
        $tree[$item['label']] = array_column($item['children'], 'label');
    }

    return $tree;
}
