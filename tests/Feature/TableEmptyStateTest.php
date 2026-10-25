<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\AdminPanel;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidTableEmptyState;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Tables\Actions\TableAction;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Admin\Tests\Fixtures\Book;

/**
 * Lets each test define its own empty state without declaring a table class per test.
 */
class ShelfTable extends Table
{
    /** @var array<string, mixed>|null */
    public static ?array $emptyState = null;

    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }

    public function emptyState(): ?array
    {
        return static::$emptyState;
    }
}

class PlainShelfTable extends Table
{
    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }
}

function emptyStateOf(Table $table): mixed
{
    return json_decode(json_encode($table), true)['emptyState'];
}

beforeEach(function () {
    Book::createTable();
    ShelfTable::$emptyState = null;
});

it('has no empty state unless the table defines one', function () {
    expect(emptyStateOf(PlainShelfTable::make()))->toBeNull();
});

it('describes the empty state with an Icon case', function () {
    ShelfTable::$emptyState = [
        'title' => 'No books yet',
        'description' => 'Books appear here once you add them.',
        'icon' => Icon::Book,
    ];

    expect(emptyStateOf(ShelfTable::make()))->toBe([
        'title' => 'No books yet',
        'description' => 'Books appear here once you add them.',
        'icon' => 'Book',
        'actions' => [],
    ]);
});

it('accepts the icon as a Remix icon name', function () {
    ShelfTable::$emptyState = ['title' => 'No books yet', 'icon' => 'file-list-3'];

    expect(emptyStateOf(ShelfTable::make())['icon'])->toBe('FileList3');
});

it('only needs a title', function () {
    ShelfTable::$emptyState = ['title' => 'No books yet'];

    expect(emptyStateOf(ShelfTable::make()))->toBe([
        'title' => 'No books yet',
        'description' => null,
        'icon' => null,
        'actions' => [],
    ]);
});

it('offers actions that resolve the empty state', function () {
    ShelfTable::$emptyState = [
        'title' => 'No books yet',
        'actions' => [TableAction::make('Add book')->to('/admin/books/create')->icon(Icon::Add)],
    ];

    expect(emptyStateOf(ShelfTable::make())['actions'])->toBe([
        ['label' => 'Add book', 'method' => 'get', 'url' => '/admin/books/create', 'icon' => 'Add'],
    ]);
});

it('rejects an invalid empty state', function (array $emptyState, string $code) {
    ShelfTable::$emptyState = $emptyState;

    try {
        json_encode(ShelfTable::make(), JSON_THROW_ON_ERROR);
    } catch (InvalidTableEmptyState $exception) {
        expect($exception->errorCode())->toBe($code)
            ->and($exception->getMessage())->toContain('ShelfTable');

        return;
    }

    test()->fail('Expected an InvalidTableEmptyState exception.');
})->with([
    'missing title' => [['description' => 'Nothing here.'], 'larasell-admin.table.empty-state.missing-title'],
    'blank title' => [['title' => '  '], 'larasell-admin.table.empty-state.missing-title'],
    'title that is not a string' => [['title' => 42], 'larasell-admin.table.empty-state.invalid-value'],
    'description that is not a string' => [['title' => 'No books yet', 'description' => ['a']], 'larasell-admin.table.empty-state.invalid-value'],
    'unknown icon' => [['title' => 'No books yet', 'icon' => 'not-an-icon'], 'larasell-admin.table.empty-state.unknown-icon'],
    'empty icon' => [['title' => 'No books yet', 'icon' => ''], 'larasell-admin.table.empty-state.empty-icon'],
    'whitespace icon' => [['title' => 'No books yet', 'icon' => '   '], 'larasell-admin.table.empty-state.empty-icon'],
    'icon with a style suffix' => [['title' => 'No books yet', 'icon' => 'book-line'], 'larasell-admin.table.empty-state.icon-style-variant'],
    'icon of the wrong type' => [['title' => 'No books yet', 'icon' => 12], 'larasell-admin.table.empty-state.invalid-value'],
    'unknown key' => [['title' => 'No books yet', 'subtitle' => 'Oops'], 'larasell-admin.table.empty-state.unknown-key'],
]);

it('says which rule invalid empty state actions break', function (mixed $actions, string $expected) {
    ShelfTable::$emptyState = ['title' => 'No books yet', 'actions' => value($actions)];

    try {
        json_encode(ShelfTable::make(), JSON_THROW_ON_ERROR);
    } catch (InvalidTableEmptyState $exception) {
        expect($exception->errorCode())->toBe('larasell-admin.table.empty-state.invalid-value')
            ->and($exception->getMessage())->toEndWith("[actions] must be {$expected}.");

        return;
    }

    test()->fail('Expected an InvalidTableEmptyState exception.');
})->with([
    'a single action instead of an array' => [fn () => TableAction::make('Add book')->to('/a'), 'an array'],
    'actions keyed by name' => [fn () => ['add' => TableAction::make('Add book')->to('/a')], 'a list'],
    'an array instead of a TableAction' => [[['label' => 'Add book', 'url' => '/a']], 'a list of TableAction'],
]);

it('sends the empty state to the table page', function () {
    ShelfTable::$emptyState = ['title' => 'No books yet', 'icon' => Icon::Book];
    AdminPanel::resolve()->routes(function () {
        Route::index('shelf', ShelfTable::class);
    });
    Route::getRoutes()->refreshNameLookups();
    $admin = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get('/admin/shelf')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('TablePage')
            ->where('table.emptyState', ['title' => 'No books yet', 'description' => null, 'icon' => 'Book', 'actions' => []]));
});
