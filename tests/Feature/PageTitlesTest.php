<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\Inertia;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Admin\Tests\Fixtures\Book;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Translatable;

class ReadingListTable extends Table
{
    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }
}

/**
 * The document's only <title>, so a second one from Inertia's head would fail the test.
 */
function pageTitle(TestResponse $response): string
{
    $response->assertOk();

    preg_match_all('/<title\b[^>]*>(.*?)<\/title>/s', $response->getContent(), $titles);

    expect($titles[1])->toHaveCount(1);

    return html_entity_decode($titles[1][0]);
}

function signedInAdmin(): void
{
    $admin = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    test()->actingAs($admin, 'larasell-admin');
}

it('titles the login page', function () {
    expect(pageTitle($this->get('/admin/login')))->toBe('Sign in - Admin');
});

it('titles the signed-in pages', function (string $uri, string $title) {
    signedInAdmin();

    expect(pageTitle($this->get($uri)))->toBe($title);
})->with([
    'dashboard' => ['/admin', 'Dashboard - Admin'],
    'categories without any' => ['/admin/categories', 'Categories - Admin'],
    'new category' => ['/admin/categories/create', 'New category - Admin'],
    'products' => ['/admin/products', 'Products - Admin'],
    'customers' => ['/admin/customers', 'Customers - Admin'],
    'profile' => ['/admin/profile', 'Profile - Admin'],
    'admins' => ['/admin/settings/admins', 'Admins - Admin'],
    'new admin' => ['/admin/settings/admins/create', 'New admin - Admin'],
]);

it('titles an admin\'s page with their name', function () {
    signedInAdmin();

    expect(pageTitle($this->get('/admin/settings/admins/1/edit')))->toBe('Ada Lovelace - Admin');
});

it('titles category pages with the category name', function (string $uri, string $title) {
    signedInAdmin();

    Category::create([
        'name' => Translatable::fromString('Shoes & Boots'),
        'slug' => Translatable::fromString('shoes-boots'),
    ]);

    expect(pageTitle($this->get($uri)))->toBe($title);
})->with([
    'category' => ['/admin/categories/1', 'Shoes & Boots - Admin'],
    'new category inside it' => ['/admin/categories/create?parent=1', 'New category in Shoes & Boots - Admin'],
]);

it('titles app routes with their own title, their table, or Admin', function (string $uri, string $title) {
    Book::createTable();
    signedInAdmin();

    AdminPanel::resolve()->routes(function () {
        Route::index('reading-list', ReadingListTable::class);
        Route::get('untitled', fn () => Inertia::render('Home'));
        Route::get('titled', fn () => Inertia::render('Home'))->withHead(title: 'Reports');
    });

    expect(pageTitle($this->get($uri)))->toBe($title);
})->with([
    'table' => ['/admin/reading-list', 'Reading list - Admin'],
    'without a title' => ['/admin/untitled', 'Admin'],
    'with a title' => ['/admin/titled', 'Reports - Admin'],
]);

it('shares the title with Inertia visits', function () {
    signedInAdmin();

    $this->get('/admin/customers', ['X-Inertia' => 'true'])
        ->assertJsonPath('props.head', ['<title data-inertia="title">Customers - Admin</title>']);
});
