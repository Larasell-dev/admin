<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Inertia\Inertia;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Table;
use Larasell\Admin\Tests\Fixtures\Book;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Models\Customer;
use Larasell\Larasell\Models\Order;
use Larasell\Larasell\Price;
use Larasell\Larasell\SalesChannels\SalesContext;
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
    // Act & Assert
    expect(pageTitle($this->get(route('larasell.admin.login'))))->toBe('Sign in - Admin');
});

it('titles the signed-in pages', function (string $uri, string $title) {
    // Arrange
    signedInAdmin();

    // Act & Assert
    expect(pageTitle($this->get($uri)))->toBe($title);
})->with([
    'dashboard' => ['/admin', 'Dashboard - Admin'],
    'categories without any' => ['/admin/categories', 'Categories - Admin'],
    'new category' => ['/admin/categories/create', 'New category - Admin'],
    'products' => ['/admin/products', 'Products - Admin'],
    'customers' => ['/admin/customers', 'Customers - Admin'],
    'new customer' => ['/admin/customers/create', 'New customer - Admin'],
    'profile' => ['/admin/profile', 'Profile - Admin'],
    'team' => ['/admin/settings/team', 'Team - Admin'],
    'new member' => ['/admin/settings/team/create', 'New member - Admin'],
    'API tokens' => ['/admin/settings/api-tokens', 'API tokens - Admin'],
    'new API token' => ['/admin/settings/api-tokens/create', 'New API token - Admin'],
]);

it('titles an admin\'s page with their name', function () {
    // Arrange
    signedInAdmin();

    // Act & Assert
    expect(pageTitle($this->get(route('larasell.admin.settings.team.edit', 1))))->toBe('Ada Lovelace - Admin');
});

it('titles a customer\'s page with their name', function () {
    // Arrange
    signedInAdmin();

    $customer = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    // Act & Assert
    expect(pageTitle($this->get(route('larasell.admin.customers.edit', $customer))))->toBe('Ada Lovelace - Admin');
});

it('titles an order\'s page with its number', function () {
    // Arrange
    signedInAdmin();

    $order = Order::createIn(SalesContext::for('default', 'en'), ['number' => '2026-000123', 'customer_email' => 'ada@example.com', 'customer_name' => 'Ada Lovelace', 'currency' => 'EUR', 'subtotal' => Price::of(0), 'total' => Price::of(0)]);

    // Act & Assert
    expect(pageTitle($this->get(route('larasell.admin.orders.show', $order))))->toBe('Order #2026-000123 - Admin');
});

it('titles category pages with the category name', function (string $uri, string $title) {
    // Arrange
    signedInAdmin();

    Category::create([
        'name' => Translatable::fromString('Shoes & Boots'),
    ]);

    // Act & Assert
    expect(pageTitle($this->get($uri)))->toBe($title);
})->with([
    'category' => ['/admin/categories/1', 'Shoes & Boots - Admin'],
    'new category inside it' => ['/admin/categories/create?parent=1', 'New category in Shoes & Boots - Admin'],
]);

it('titles app routes with their own title, their table, or Admin', function (string $uri, string $title) {
    // Arrange
    Book::createTable();
    signedInAdmin();

    AdminPanel::resolve()->routes(function () {
        Route::index('reading-list', ReadingListTable::class);
        Route::get('untitled', fn () => Inertia::render('Home'));
        Route::get('titled', fn () => Inertia::render('Home'))->withHead(title: 'Reports');
    });

    // Act & Assert
    expect(pageTitle($this->get($uri)))->toBe($title);
})->with([
    'table' => ['/admin/reading-list', 'Reading list - Admin'],
    'without a title' => ['/admin/untitled', 'Admin'],
    'with a title' => ['/admin/titled', 'Reports - Admin'],
]);

it('shares the title with Inertia visits', function () {
    // Arrange
    signedInAdmin();

    // Act & Assert
    $this->get(route('larasell.admin.customers.index'), ['X-Inertia' => 'true'])
        ->assertJsonPath('props.head', ['<title data-inertia="title">Customers - Admin</title>']);
});
