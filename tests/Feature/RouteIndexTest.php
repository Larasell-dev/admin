<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Admin\Tests\Fixtures\Book;

class LibraryTable extends Table
{
    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }
}

beforeEach(function () {
    Book::createTable();

    AdminPanel::resolve()->routes(function () {
        Route::index('shop/library', LibraryTable::class);
    });

    Route::getRoutes()->refreshNameLookups();
});

it('renders the table page for an index route', function () {
    $admin = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $book = Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

    expect(route('larasell.admin.shop.library.index', absolute: false))->toBe('/admin/shop/library');

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/shop/library')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('TablePage')
            ->where('table.title', 'Library')
            ->where('table.columns', [['name' => 'title', 'label' => 'Title', 'type' => 'text']])
            ->where('table.rows', [['id' => $book->id, 'title' => 'Dune', '_actions' => [], '_link' => null]]));
});

it('requires a signed-in admin', function () {
    $this->get('/admin/shop/library')->assertRedirect('/admin/login');
});
