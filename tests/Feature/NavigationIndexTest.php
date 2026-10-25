<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Http\Controllers\TableController;
use Larasell\Admin\Http\Middleware\Authenticate;
use Larasell\Admin\NavigationGroup;
use Larasell\Admin\Tests\Fixtures\Tables\Books;
use Larasell\Admin\Tests\Fixtures\Tables\Library\AudioBookTable;

it('registers a table route and its top-level navigation item in one call', function () {
    $this->bootingWith(function () {
        Navigation::index('Books', 'shop/books', Books::class, Icon::Book)->position(1500);
    });

    $route = Route::getRoutes()->getByName('larasell.admin.shop.books.index');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('admin/shop/books')
        ->and($route->getActionName())->toBe(TableController::class)
        ->and($route->defaults['table'])->toBe(Books::class)
        ->and($route->gatherMiddleware())->toContain(Authenticate::class);

    $tree = Navigation::tree();

    // Position 1500 puts it right after Home.
    expect($tree[1]->toArray())->toBe([
        'id' => 'larasell.admin.shop.books.index',
        'label' => 'Books',
        'url' => route('larasell.admin.shop.books.index'),
        'icon' => 'Book',
        'children' => [],
    ]);
});

it('registers a table route and its nested navigation item in one call', function () {
    $this->bootingWith(function () {
        Navigation::index('Books', 'shop/books', Books::class, Icon::Book)
            ->children(function (NavigationGroup $books): void {
                $books->index('Audio books', 'shop/audio-books', AudioBookTable::class, 'headphone');
            });
    });

    $route = Route::getRoutes()->getByName('larasell.admin.shop.audio-books.index');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('admin/shop/audio-books')
        ->and($route->defaults['table'])->toBe(AudioBookTable::class)
        ->and($route->gatherMiddleware())->toContain(Authenticate::class);

    $books = collect(Navigation::tree())->firstWhere('id', 'larasell.admin.shop.books.index');

    expect($books->toArray()['children'])->toBe([[
        'id' => 'larasell.admin.shop.audio-books.index',
        'label' => 'Audio books',
        'url' => route('larasell.admin.shop.audio-books.index'),
        'icon' => 'Headphone',
        'children' => [],
    ]]);
});
