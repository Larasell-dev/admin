<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Platform\Navigation\Data\NavigationItemData;
use Larasell\Admin\Platform\Navigation\Exceptions\InvalidNavigationItem;
use Larasell\Admin\Platform\Navigation\Facades\Navigation as NavigationFacade;
use Larasell\Admin\Platform\Navigation\Navigation;
use Larasell\Admin\Platform\Navigation\NavItem;
use Larasell\Admin\Platform\Panel\Icon;

beforeEach(function () {
    Route::get('/admin/home', fn () => '')->name('larasell.admin.home');
    Route::get('/admin/settings', fn () => '')->name('larasell.admin.settings');
    Route::get('/admin/notes', fn () => '')->name('larasell.admin.notes');
});

it('resolves one shared instance from the container and the facade', function () {
    // Act & Assert
    expect(Navigation::resolve())->toBe(app(Navigation::class))
        ->toBe(NavigationFacade::getFacadeRoot());
});

it('registers navigation items and prefixes short route names', function () {
    // Arrange
    $navigation = new Navigation;

    // Act
    $navigation->add('Home', 'home', Icon::Home);
    $navigation->add('Settings', 'larasell.admin.settings', 'settings-3');

    // Assert
    expect(serialized($navigation))->toBe([
        ['id' => 'larasell.admin.home', 'label' => 'Home', 'url' => route('larasell.admin.home'), 'icon' => 'Home', 'children' => []],
        ['id' => 'larasell.admin.settings', 'label' => 'Settings', 'url' => route('larasell.admin.settings'), 'icon' => 'Settings3', 'children' => []],
    ]);
});

it('appends navigation items in registration order by default', function () {
    // Arrange
    $navigation = new Navigation;

    // Act
    $navigation->add('Settings', 'larasell.admin.settings', 'settings-3');
    $navigation->add('Home', 'larasell.admin.home', 'home');

    // Assert
    expect(labels($navigation))->toBe(['Settings', 'Home']);
});

it('sorts navigation items by position in ascending order', function () {
    // Arrange
    $navigation = new Navigation;

    // Act
    $navigation->add('Home', 'larasell.admin.home', 'home')->position(500);
    $navigation->add('Settings', 'larasell.admin.settings', 'settings-3')->position(100);
    $navigation->add('Notes', 'larasell.admin.notes', 'sticky-note')->position(300);

    // Assert
    expect(labels($navigation))->toBe(['Settings', 'Notes', 'Home']);
});

it('puts unpositioned items below positioned ones', function () {
    // Arrange
    $navigation = new Navigation;

    // Act
    $navigation->add('Notes', 'larasell.admin.notes', 'sticky-note');
    $navigation->add('Home', 'larasell.admin.home', 'home')->position(900);
    $navigation->add('Settings', 'larasell.admin.settings', 'settings-3');

    // Assert
    expect(labels($navigation))->toBe(['Home', 'Notes', 'Settings']);
});

it('sorts items with the same position by id, so translated labels keep the order', function () {
    // Arrange
    $navigation = new Navigation;

    // Act
    // Label order (Accounts, Start, Zettel) differs from id order (home, notes, settings).
    $navigation->add('Accounts', 'larasell.admin.settings', 'settings-3')->position(100);
    $navigation->add('Zettel', 'larasell.admin.notes', 'sticky-note')->position(100);
    $navigation->add('Start', 'larasell.admin.home', 'home')->position(100);

    // Assert
    expect(labels($navigation))->toBe(['Start', 'Zettel', 'Accounts']);
});

it('places a navigation item before another item by id', function () {
    // Arrange
    $navigation = new Navigation;

    // Act
    $navigation->add('Home', 'larasell.admin.home', 'home');
    $navigation->add('Settings', 'larasell.admin.settings', 'settings-3');
    $navigation->add('Notes', 'larasell.admin.notes', 'sticky-note')->before('larasell.admin.settings');

    // Assert
    expect(labels($navigation))->toBe(['Home', 'Notes', 'Settings']);
});

it('places a navigation item after another item by enum case', function () {
    // Arrange
    $navigation = new Navigation;

    // Act
    $navigation->add('Home', 'larasell.admin.home', 'home');
    $navigation->add('Settings', 'larasell.admin.settings', 'settings-3');
    $navigation->add('Notes', 'larasell.admin.notes', 'sticky-note')->after(NavItem::Home);

    // Assert
    expect(labels($navigation))->toBe(['Home', 'Notes', 'Settings']);
});

it('resolves targets registered after the item that refers to them', function () {
    // Arrange
    $navigation = new Navigation;

    // Act
    $navigation->add('Notes', 'larasell.admin.notes', 'sticky-note')->after('larasell.admin.settings');
    $navigation->add('Settings', 'larasell.admin.settings', 'settings-3')->after('larasell.admin.home');
    $navigation->add('Home', 'larasell.admin.home', 'home');

    // Assert
    expect(labels($navigation))->toBe(['Home', 'Settings', 'Notes']);
});

it('throws when a navigation id is registered twice', function () {
    // Arrange
    $navigation = new Navigation;

    // Act & Assert
    $navigation->add('Home', 'larasell.admin.home', 'home');
    $navigation->add('Start', 'home', 'home');
})->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.home] is already registered.');

it('throws when the relative target does not exist', function () {
    // Arrange
    $navigation = new Navigation;

    $navigation->add('Home', 'larasell.admin.home', 'home');
    $navigation->add('Notes', 'larasell.admin.notes', 'sticky-note')->before('larasell.admin.missing');

    // Act & Assert
    $navigation->tree();
})->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.notes] targets unknown item [larasell.admin.missing].');

it('throws when an item route is not registered', function (Closure $register) {
    // Arrange
    $navigation = new Navigation;
    $navigation->add('Home', 'home', 'home');
    $register($navigation);

    // Act & Assert
    $navigation->tree();
})->with([
    'top-level item' => [fn ($navigation) => $navigation->add('Reports', 'reports', 'file-list-3')],
    'child' => [fn ($navigation) => $navigation->add('Reports', 'reports', 'file-list-3')->under('larasell.admin.home')],
])->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.reports] route is not registered.');

it('throws when relative placements form a cycle', function () {
    // Arrange
    $navigation = new Navigation;

    $navigation->add('Home', 'larasell.admin.home', 'home')->before('larasell.admin.settings');
    $navigation->add('Settings', 'larasell.admin.settings', 'settings-3')->before('larasell.admin.home');

    // Act & Assert
    $navigation->tree();
})->throws(InvalidNavigationItem::class, 'Navigation items [larasell.admin.home, larasell.admin.settings] form a placement cycle.');

it('has a registered navigation item for every NavItem case', function () {
    // Arrange
    $ids = array_map(fn (NavItem $case): string => $case->value, NavItem::cases());

    // Act & Assert
    foreach ($ids as $id) {
        expect(Route::has($id))->toBeTrue();
    }

    expect(array_column(Navigation::resolve()->tree(), 'id'))->toContain(...$ids);
});

it('positions NavItem cases in steps of 1000 in declaration order', function () {
    // Act & Assert
    expect(array_map(fn (NavItem $case): int => $case->position(), NavItem::cases()))
        ->toBe([1000, 2000, 3000, 4000, 5000, 6000]);
});

it('lists NavItem labels keyed by route', function () {
    // Act & Assert
    expect(NavItem::all())->toBe([
        'larasell.admin.home' => 'Home',
        'larasell.admin.products.index' => 'Products',
        'larasell.admin.categories.index' => 'Categories',
        'larasell.admin.orders.index' => 'Orders',
        'larasell.admin.customers.index' => 'Customers',
        'larasell.admin.settings' => 'Settings',
    ]);
});

it('places built-in navigation items at their NavItem position', function () {
    // Arrange
    $this->bootingWith(function () {
        Route::get('/admin/notes', fn () => '')->name('larasell.admin.notes');
        NavigationFacade::add('Notes', 'larasell.admin.notes', 'sticky-note')->position(1500);
    });

    // Act & Assert
    expect(array_column(Navigation::resolve()->tree(), 'id'))->toBe([
        'larasell.admin.home',
        'larasell.admin.notes',
        'larasell.admin.products.index',
        'larasell.admin.categories.index',
        'larasell.admin.orders.index',
        'larasell.admin.customers.index',
        'larasell.admin.settings',
    ]);
});

it('freezes the navigation once the app has booted', function () {
    // Act & Assert
    NavigationFacade::add('Notes', 'larasell.admin.notes', 'sticky-note');
})->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.notes] was changed after the admin panel booted.');

it('throws when an item is added to a frozen navigation', function () {
    // Arrange
    $navigation = new Navigation;
    $navigation->freeze();

    // Act & Assert
    $navigation->add('Notes', 'notes', 'sticky-note');
})->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.notes] was changed after the admin panel booted.');

it('throws when a registered item is changed after the navigation froze', function (Closure $change) {
    // Arrange
    $navigation = new Navigation;
    $navigation->add('Home', 'home', 'home');
    $notes = $navigation->add('Notes', 'notes', 'sticky-note');
    $navigation->freeze();

    // Act & Assert
    $change($notes);
})->with([
    'position' => [fn ($item) => $item->position(1)],
    'before' => [fn ($item) => $item->before('larasell.admin.home')],
    'after' => [fn ($item) => $item->after('larasell.admin.home')],
    'under' => [fn ($item) => $item->under('larasell.admin.home')],
    'children' => [fn ($item) => $item->children(fn () => null)],
])->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.notes] was changed after the admin panel booted.');

describe('nested navigation', function () {
    beforeEach(function () {
        Route::get('/admin/settings/taxes', fn () => '')->name('larasell.admin.taxes');
        Route::get('/admin/settings/shipping', fn () => '')->name('larasell.admin.shipping');
        Route::get('/admin/settings/payments', fn () => '')->name('larasell.admin.payments');
    });

    it('nests an item under its parent and serializes children', function () {
        // Arrange
        $navigation = new Navigation;

        // Act
        $navigation->add('Home', 'home', 'home');
        $navigation->add('Settings', 'settings', 'settings-3');
        $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');

        // Assert
        expect(serialized($navigation))->toBe([
            ['id' => 'larasell.admin.home', 'label' => 'Home', 'url' => route('larasell.admin.home'), 'icon' => 'Home', 'children' => []],
            ['id' => 'larasell.admin.settings', 'label' => 'Settings', 'url' => route('larasell.admin.settings'), 'icon' => 'Settings3', 'children' => [
                ['id' => 'larasell.admin.taxes', 'label' => 'Taxes', 'url' => route('larasell.admin.taxes'), 'icon' => 'Percent', 'children' => []],
            ]],
        ]);
    });

    it('defines children on the parent and keeps the parent chainable', function () {
        // Arrange
        $navigation = new Navigation;

        // Act
        $navigation->add('Home', 'home', 'home');
        $navigation->add('Settings', 'settings', 'settings-3')
            ->children(function ($children) {
                $children->add('Taxes', 'taxes', 'percent');
                $children->add('Shipping', 'larasell.admin.shipping', 'truck');
            })
            ->before('larasell.admin.home');

        // Assert
        expect(tree($navigation))->toBe([
            'Settings' => ['Taxes', 'Shipping'],
            'Home' => [],
        ]);
    });

    it('nests under a parent given as a NavItem case', function () {
        // Arrange
        $navigation = new Navigation;

        // Act
        $navigation->add('Home', NavItem::Home->value, 'home');
        $navigation->add('Notes', 'notes', 'sticky-note')->under(NavItem::Home);

        // Assert
        expect(tree($navigation))->toBe(['Home' => ['Notes']]);
    });

    it('nests under a parent registered later', function () {
        // Arrange
        $navigation = new Navigation;

        // Act
        $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');
        $navigation->add('Settings', 'settings', 'settings-3');

        // Assert
        expect(tree($navigation))->toBe(['Settings' => ['Taxes']]);
    });

    it('merges children from children() and under() into one ordered group', function () {
        // Arrange
        $navigation = new Navigation;

        // Act
        $navigation->add('Settings', 'settings', 'settings-3')->children(function ($children) {
            $children->add('Taxes', 'taxes', 'percent')->position(2000);
            $children->add('Payments', 'payments', 'bank-card')->position(1000);
        });
        // A plugin adds to the group without access to the parent object.
        $navigation->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->position(1500);

        // Assert
        expect(tree($navigation))->toBe(['Settings' => ['Payments', 'Shipping', 'Taxes']]);
    });

    it('orders children by their own positions, independent of top-level positions', function () {
        // Arrange
        $navigation = new Navigation;

        // Act
        $navigation->add('Home', 'home', 'home')->position(500);
        $navigation->add('Settings', 'settings', 'settings-3')->position(600);
        // 1000 would sort after both top-level items if positions were shared.
        $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->position(1000);
        $navigation->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->position(0);

        // Assert
        expect(tree($navigation))->toBe([
            'Home' => [],
            'Settings' => ['Shipping', 'Taxes'],
        ]);
    });

    it('accepts under() before or after position()', function () {
        // Arrange
        $navigation = new Navigation;

        // Act
        $navigation->add('Settings', 'settings', 'settings-3');
        $navigation->add('Taxes', 'taxes', 'percent')->position(20)->under('larasell.admin.settings');
        $navigation->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->position(10);

        // Assert
        expect(tree($navigation))->toBe(['Settings' => ['Shipping', 'Taxes']]);
    });

    it('puts unpositioned children after positioned ones in registration order', function () {
        // Arrange
        $navigation = new Navigation;

        // Act
        $navigation->add('Settings', 'settings', 'settings-3');
        $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');
        $navigation->add('Payments', 'payments', 'bank-card')->under('larasell.admin.settings');
        $navigation->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->position(5);

        // Assert
        expect(tree($navigation))->toBe(['Settings' => ['Shipping', 'Taxes', 'Payments']]);
    });

    it('sorts children with the same position by id', function () {
        // Arrange
        $navigation = new Navigation;

        // Act
        $navigation->add('Settings', 'settings', 'settings-3');
        $navigation->add('Abgaben', 'taxes', 'percent')->under('larasell.admin.settings')->position(1);
        $navigation->add('Zahlungen', 'payments', 'bank-card')->under('larasell.admin.settings')->position(1);

        // Assert
        expect(tree($navigation))->toBe(['Settings' => ['Zahlungen', 'Abgaben']]);
    });

    it('places a child before or after a sibling', function () {
        // Arrange
        $navigation = new Navigation;

        // Act
        $navigation->add('Settings', 'settings', 'settings-3');
        $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');
        $navigation->add('Payments', 'payments', 'bank-card')->under('larasell.admin.settings')->before('larasell.admin.taxes');
        $navigation->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->after('larasell.admin.payments');

        // Assert
        expect(tree($navigation))->toBe(['Settings' => ['Payments', 'Shipping', 'Taxes']]);
    });

    it('moves children along when the parent is placed relatively', function () {
        // Arrange
        $navigation = new Navigation;

        // Act
        $navigation->add('Home', 'home', 'home');
        $navigation->add('Notes', 'notes', 'sticky-note');
        $navigation->add('Settings', 'settings', 'settings-3')->before('larasell.admin.notes');
        $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');

        // Assert
        expect(tree($navigation))->toBe([
            'Home' => [],
            'Settings' => ['Taxes'],
            'Notes' => [],
        ]);
    });

    it('throws when a child targets an item outside its group', function (Closure $register, string $message) {
        // Arrange
        $navigation = new Navigation;

        $navigation->add('Home', 'home', 'home');
        $navigation->add('Notes', 'notes', 'sticky-note');
        $navigation->add('Settings', 'settings', 'settings-3');
        $navigation->add('Payments', 'payments', 'bank-card')->under('larasell.admin.home');
        $register($navigation);

        // Act & Assert
        expect(fn () => $navigation->tree())->toThrow(InvalidNavigationItem::class, $message);
    })->with([
        'child targets a top-level item' => [
            fn ($navigation) => $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->after('larasell.admin.home'),
            'Navigation item [larasell.admin.taxes] cannot be placed relative to [larasell.admin.home], which is not in the same group.',
        ],
        'top-level item targets a child' => [
            fn ($navigation) => $navigation->add('Taxes', 'taxes', 'percent')->before('larasell.admin.payments'),
            'Navigation item [larasell.admin.taxes] cannot be placed relative to [larasell.admin.payments], which is not in the same group.',
        ],
        'child targets a child of another parent' => [
            fn ($navigation) => $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->after('larasell.admin.payments'),
            'Navigation item [larasell.admin.taxes] cannot be placed relative to [larasell.admin.payments], which is not in the same group.',
        ],
        'child targets its own parent' => [
            fn ($navigation) => $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->before('larasell.admin.settings'),
            'Navigation item [larasell.admin.taxes] cannot be placed relative to [larasell.admin.settings], which is not in the same group.',
        ],
    ]);

    it('throws when nesting goes deeper than one level', function (Closure $register) {
        // Arrange
        $navigation = new Navigation;

        $register($navigation);

        // Act & Assert
        $navigation->tree();
    })->with([
        'grandchild registered last' => [function ($navigation) {
            $navigation->add('Settings', 'settings', 'settings-3');
            $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');
            $navigation->add('Shipping', 'shipping', 'truck')->under('larasell.admin.taxes');
        }],
        'grandchild registered first' => [function ($navigation) {
            $navigation->add('Shipping', 'shipping', 'truck')->under('larasell.admin.taxes');
            $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings');
            $navigation->add('Settings', 'settings', 'settings-3');
        }],
        'children() inside children()' => [function ($navigation) {
            $navigation->add('Settings', 'settings', 'settings-3')->children(function ($children) {
                $children->add('Taxes', 'taxes', 'percent')->children(function ($grandchildren) {
                    $grandchildren->add('Shipping', 'shipping', 'truck');
                });
            });
        }],
        'children() on an item placed under() another' => [function ($navigation) {
            $navigation->add('Settings', 'settings', 'settings-3');
            $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->children(function ($children) {
                $children->add('Shipping', 'shipping', 'truck');
            });
        }],
    ])->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.shipping] is nested under [larasell.admin.taxes], which is itself nested.');

    it('throws when the parent does not exist', function () {
        // Arrange
        $navigation = new Navigation;

        $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.missing');

        // Act & Assert
        $navigation->tree();
    })->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.taxes] is nested under unknown item [larasell.admin.missing].');

    it('throws when an item is nested under itself', function () {
        // Act & Assert
        (new Navigation)->add('Taxes', 'taxes', 'percent')->under('larasell.admin.taxes');
    })->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.taxes] cannot be nested under itself.');

    it('throws when an item is nested twice', function () {
        // Arrange
        $navigation = new Navigation;

        // Act & Assert
        $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->under('larasell.admin.home');
    })->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.taxes] is already nested under [larasell.admin.settings].');

    it('throws when children() re-nests an item that is already nested elsewhere', function () {
        // Arrange
        $navigation = new Navigation;

        // Act & Assert
        $navigation->add('Settings', 'settings', 'settings-3')->children(function ($children) {
            $children->add('Taxes', 'taxes', 'percent')->under('larasell.admin.home');
        });
    })->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.taxes] is already nested under [larasell.admin.settings].');

    it('throws when a child reuses an id from another level', function (Closure $register) {
        // Act & Assert
        $register(new Navigation);
    })->with([
        'child after top-level' => [function ($navigation) {
            $navigation->add('Settings', 'settings', 'settings-3');
            $navigation->add('Taxes', 'settings', 'percent')->under('larasell.admin.home');
        }],
        'child defined with children()' => [function ($navigation) {
            $navigation->add('Settings', 'settings', 'settings-3')->children(function ($children) {
                $children->add('Settings again', 'settings', 'percent');
            });
        }],
    ])->throws(InvalidNavigationItem::class, 'Navigation item [larasell.admin.settings] is already registered.');

    it('throws on a placement cycle among children and names only those', function () {
        // Arrange
        $navigation = new Navigation;

        $navigation->add('Settings', 'settings', 'settings-3');
        $navigation->add('Taxes', 'taxes', 'percent')->under('larasell.admin.settings')->before('larasell.admin.shipping');
        $navigation->add('Shipping', 'shipping', 'truck')->under('larasell.admin.settings')->before('larasell.admin.taxes');
        $navigation->add('Payments', 'payments', 'bank-card')->under('larasell.admin.settings');

        // Act & Assert
        $navigation->tree();
    })->throws(InvalidNavigationItem::class, 'Navigation items [larasell.admin.shipping, larasell.admin.taxes] form a placement cycle.');

    it('accepts up to the children limit per parent and throws beyond it', function (int $count, bool $throws) {
        // Arrange
        $navigation = new Navigation;

        $navigation->add('Settings', 'settings', 'settings-3');
        for ($i = 1; $i <= $count; $i++) {
            Route::get("/admin/settings/child-{$i}", fn () => '')->name("larasell.admin.child-{$i}");
            $navigation->add("Child {$i}", "child-{$i}", 'percent')->under('larasell.admin.settings');
        }

        // Act & Assert
        $build = fn () => $navigation->tree();

        $throws
            ? expect($build)->toThrow(LogicException::class, 'Navigation item [larasell.admin.settings] has more than 100 children.')
            : expect($build())->toHaveCount(1);
    })->with([
        'at the limit' => [100, false],
        'one over' => [101, true],
    ]);

    it('counts children toward the navigation limit', function () {
        // Arrange
        $navigation = new Navigation;

        $navigation->add('Settings', 'settings', 'settings-3');
        for ($i = 1; $i < Navigation::MAX_ITEMS; $i++) {
            $navigation->add("Child {$i}", "larasell.admin.child-{$i}", 'percent')->under('larasell.admin.settings');
        }

        // Act & Assert
        $navigation->add('One too many', 'larasell.admin.overflow', 'percent');
    })->throws(LogicException::class, 'Too many navigation items.');
});

function labels(Navigation $navigation): array
{
    return array_column($navigation->tree(), 'label');
}

/**
 * The navigation as the sidebar receives it.
 */
function serialized(Navigation $navigation): array
{
    return array_map(fn (NavigationItemData $item): array => $item->toArray(), $navigation->tree());
}

/**
 * The navigation as top-level label => list of child labels, in order.
 *
 * @return array<string, list<string>>
 */
function tree(Navigation $navigation): array
{
    $tree = [];
    foreach ($navigation->tree() as $item) {
        $tree[$item->label] = array_column($item->children, 'label');
    }

    return $tree;
}
