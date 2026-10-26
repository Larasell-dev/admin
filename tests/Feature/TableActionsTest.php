<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidTableAction;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Actions\TableAction;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Admin\Tests\Fixtures\AudioBook;
use Larasell\Admin\Tests\Fixtures\Book;
use Larasell\Admin\Tests\Fixtures\Tables\AudioBookActionsTable;
use Larasell\Admin\Tests\Fixtures\Tables\AuthorBookActionsTable;
use Larasell\Admin\Tests\Fixtures\Tables\BookActionsTable;

class BooksWithoutActionsTable extends Table
{
    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }
}

/**
 * Lives outside the configured tables namespace, so its actions have no endpoint.
 */
class OutsideNamespaceTable extends Table
{
    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }

    public function actions(): array
    {
        return [Action::make('Delete', handle: fn (Book $book) => $book->delete())];
    }
}

/**
 * Takes an author like AuthorBookActionsTable, but doesn't remember it for its actions.
 */
class ForgetfulAuthorBookActionsTable extends BookActionsTable
{
    public function __construct(public string $author) {}
}

/**
 * Lets each test define its own header actions without declaring a table class per test.
 */
class BookHeaderActionsTable extends Table
{
    /** @var Closure(): array<int, TableAction> */
    public static Closure $headerActions;

    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }

    public function headerActions(): array
    {
        return (static::$headerActions)();
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function headerActions(Closure $actions): array
{
    BookHeaderActionsTable::$headerActions = $actions;

    return json_decode(json_encode(BookHeaderActionsTable::make()), true)['headerActions'];
}

function withActions(Closure $actions): void
{
    BookActionsTable::$actions = $actions;
}

/**
 * @param  class-string<BookActionsTable>  $table
 * @return array<int, array<string, mixed>>
 */
function rowActions(int $row = 0, string $table = BookActionsTable::class): array
{
    return json_decode(json_encode($table::make()), true)['rows'][$row]['_actions'];
}

function signIn(): void
{
    test()->actingAs(
        Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']),
        'larasell-admin',
    );
}

function actionUrl(Book $book, string $action): string
{
    return "/admin/tables/tables.book-actions-table/{$book->id}/actions/{$action}";
}

beforeEach(function () {
    config(['larasell.admin.panel.tables.namespace' => 'Larasell\\Admin\\Tests\\Fixtures']);
    Book::createTable();

    $this->dune = Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
    $this->emma = Book::create(['title' => 'Emma', 'author_name' => 'Jane Austen']);
});

describe('row actions', function () {
    it('has no actions when the table defines none', function () {
        $rows = json_decode(json_encode(BooksWithoutActionsTable::make()), true)['rows'];

        expect($rows[0]['_actions'])->toBe([]);
    });

    it('links to a fixed url', function () {
        withActions(fn () => [Action::make('View')->to('https://example.com/books')]);

        expect(rowActions())->toBe([
            ['name' => 'view', 'label' => 'View', 'method' => 'get', 'url' => 'https://example.com/books', 'confirm' => null, 'destructive' => false, 'disabled' => false, 'icon' => null],
        ]);
    });

    it('links to a url built from the record', function () {
        withActions(fn () => [Action::make('View')->to(fn (Book $book) => "/books/{$book->id}")]);

        expect(rowActions(0)[0]['url'])->toBe("/books/{$this->dune->id}")
            ->and(rowActions(1)[0]['url'])->toBe("/books/{$this->emma->id}");
    });

    it('links to a named route with the record as its parameter', function () {
        Route::get('books/{book}', fn () => null)->name('books.show');
        Route::getRoutes()->refreshNameLookups();

        withActions(fn () => [Action::make('View')->route('books.show')]);

        // Spelled out to check what route() produced.
        expect(rowActions(1)[0]['url'])->toBe(url("/books/{$this->emma->id}")); // @phpstan-ignore larasell.preferRouteHelper
    });

    it('posts handler actions to the action endpoint for the row', function () {
        withActions(fn () => [Action::make('Archive', handle: fn (Book $book) => null)]);

        expect(rowActions(1))->toBe([
            ['name' => 'archive', 'label' => 'Archive', 'method' => 'post', 'url' => url(actionUrl($this->emma, 'archive')), 'confirm' => null, 'destructive' => false, 'disabled' => false, 'icon' => null],
        ]);
    });

    it('derives a url-safe name from multi-word labels', function () {
        withActions(fn () => [Action::make('Send Reminder', handle: fn (Book $book) => null)]);

        expect(rowActions()[0]['name'])->toBe('send-reminder');
    });

    it('asks for confirmation with default texts', function () {
        withActions(fn () => [Action::make('Delete', handle: fn (Book $book) => null)->confirm()]);

        expect(rowActions()[0]['confirm'])->toBe([
            'title' => 'Delete book',
            'message' => 'Are you sure you want to delete this book?',
            'confirmButton' => 'Delete',
            'cancelButton' => 'Cancel',
            'confirmationText' => null,
        ]);
    });

    it('names multi-word models in plain words in the default confirmation', function () {
        withActions(fn () => [Action::make('Send Reminder', handle: fn (AudioBook $book) => null)->confirm()]);

        expect(rowActions(table: AudioBookActionsTable::class)[0]['confirm'])->toMatchArray([
            'title' => 'Send reminder audio book',
            'message' => 'Are you sure you want to send reminder this audio book?',
            'confirmButton' => 'Send Reminder',
        ]);
    });

    it('asks for confirmation with custom texts and a per-record confirmation text', function () {
        withActions(fn () => [
            Action::make('Delete', handle: fn (Book $book) => null)->confirm(
                title: 'Delete book',
                message: 'Are you sure you want to delete this book?',
                confirmButton: 'Delete book',
                cancelButton: 'Keep it',
                confirmationText: fn (Book $book) => $book->title,
            ),
        ]);

        expect(rowActions(0)[0]['confirm'])->toBe([
            'title' => 'Delete book',
            'message' => 'Are you sure you want to delete this book?',
            'confirmButton' => 'Delete book',
            'cancelButton' => 'Keep it',
            'confirmationText' => 'Dune',
        ])->and(rowActions(1)[0]['confirm']['confirmationText'])->toBe('Emma');
    });

    it('accepts a fixed confirmation text', function () {
        withActions(fn () => [Action::make('Delete', handle: fn (Book $book) => null)->confirm(confirmationText: 'delete')]);

        expect(rowActions(1)[0]['confirm']['confirmationText'])->toBe('delete');
    });

    it('can ask for confirmation before following a link', function () {
        withActions(fn () => [Action::make('View')->to('https://example.com')->confirm()]);

        expect(rowActions()[0])->toMatchArray(['method' => 'get', 'url' => 'https://example.com'])
            ->and(rowActions()[0]['confirm']['title'])->toBe('View book');
    });

    it('marks destructive actions', function () {
        withActions(fn () => [
            Action::make('Delete', handle: fn (Book $book) => null)->destructive(),
            Action::make('Archive', handle: fn (Book $book) => null),
        ]);

        expect(array_column(rowActions(), 'destructive', 'name'))->toBe(['delete' => true, 'archive' => false]);
    });

    it('marks actions destructive only when the condition holds', function () {
        withActions(fn () => [
            Action::make('Delete', handle: fn (Book $book) => null)->destructive(false),
            Action::make('Purge', handle: fn (Book $book) => null)->destructive(true),
        ]);

        expect(array_column(rowActions(), 'destructive', 'name'))->toBe(['delete' => false, 'purge' => true]);
    });

    it('can mark a confirmed link as destructive', function () {
        withActions(fn () => [Action::make('Close account')->to('https://example.com/close')->confirm()->destructive()]);

        expect(rowActions()[0])->toMatchArray(['method' => 'get', 'destructive' => true])
            ->and(rowActions()[0]['confirm'])->not->toBeNull();
    });

    it('disables actions', function () {
        withActions(fn () => [
            Action::make('Archive', handle: fn (Book $book) => null)->disabled(),
            Action::make('Publish', handle: fn (Book $book) => null)->disabled(false),
            Action::make('View')->to('https://example.com')->disabled(true),
        ]);

        expect(array_column(rowActions(), 'disabled', 'name'))->toBe(['archive' => true, 'publish' => false, 'view' => true]);
    });

    it('disables actions per record', function () {
        withActions(fn () => [
            Action::make('Archive', handle: fn (Book $book) => null)->disabled(fn (Book $book) => $book->title === 'Emma'),
        ]);

        expect(rowActions(0)[0]['disabled'])->toBeFalse()
            ->and(rowActions(1)[0]['disabled'])->toBeTrue();
    });

    it('hides actions', function () {
        withActions(fn () => [
            Action::make('Archive', handle: fn (Book $book) => null)->hidden(),
            Action::make('Publish', handle: fn (Book $book) => null)->hidden(false),
            Action::make('View')->to('https://example.com')->hidden(true),
        ]);

        expect(rowActions())->toHaveCount(1)
            ->and(rowActions()[0]['name'])->toBe('publish');
    });

    it('hides actions per record', function () {
        withActions(fn () => [
            Action::make('Archive', handle: fn (Book $book) => null)->hidden(fn (Book $book) => $book->title === 'Emma'),
            Action::make('View')->to('https://example.com'),
        ]);

        expect(array_column(rowActions(0), 'name'))->toBe(['archive', 'view'])
            ->and(array_column(rowActions(1), 'name'))->toBe(['view']);
    });

    it('shows an icon given as an Icon case', function () {
        withActions(fn () => [
            Action::make('Delete', handle: fn (Book $book) => null)->icon(Icon::DeleteBin),
            Action::make('Archive', handle: fn (Book $book) => null),
        ]);

        expect(array_column(rowActions(), 'icon', 'name'))->toBe(['delete' => 'DeleteBin', 'archive' => null]);
    });

    it('shows an icon given as its Remix icon name', function (string $icon, string $case) {
        withActions(fn () => [Action::make('View')->to('https://example.com')->icon($icon)]);

        expect(rowActions()[0]['icon'])->toBe($case);
    })->with([
        'multi-word name' => ['file-list-3', 'FileList3'],
        'name whose case is spelled differently' => ['24-hours', 'TwentyFourHours'],
    ]);

    it('rejects an icon that is not a Remix icon name', function (string $icon, string $code) {
        try {
            Action::make('Send Reminder', handle: fn (Book $book) => null)->icon($icon);
        } catch (InvalidTableAction $exception) {
            expect($exception->errorCode())->toBe($code)
                ->and($exception->getMessage())->toContain('send-reminder');

            return;
        }

        test()->fail('Expected an InvalidTableAction exception.');
    })->with([
        'unknown icon' => ['not-an-icon', 'larasell-admin.table.action.unknown-icon'],
        'empty icon' => ['', 'larasell-admin.table.action.empty-icon'],
        'whitespace icon' => ['   ', 'larasell-admin.table.action.empty-icon'],
        'line style' => ['delete-bin-line', 'larasell-admin.table.action.icon-style-variant'],
        'fill style' => ['delete-bin-fill', 'larasell-admin.table.action.icon-style-variant'],
        'case name instead of Remix name' => ['DeleteBin', 'larasell-admin.table.action.unknown-icon'],
    ]);

    it('rejects two actions with the same name', function () {
        withActions(fn () => [
            Action::make('Delete', handle: fn (Book $book) => null),
            Action::make('Delete', handle: fn (Book $book) => null),
        ]);

        rowActions();
    })->throws(LogicException::class, 'delete');

    it('rejects a handler action on a table outside the tables namespace', function () {
        json_encode(OutsideNamespaceTable::make());
    })->throws(LogicException::class, 'Larasell\\Admin\\Tests\\Fixtures');

    it('rejects an action that neither links nor handles anything', function () {
        withActions(fn () => [Action::make('Delete')->confirm()]);

        rowActions();
    })->throws(LogicException::class, 'delete');

    it('rejects configuring the same thing twice', function (Closure $configureTwice, string $method) {
        try {
            $configureTwice(Action::make('Send Reminder'));
        } catch (InvalidTableAction $exception) {
            expect($exception->errorCode())->toBe('larasell-admin.table.action.configured-twice')
                ->and($exception->getMessage())->toContain('send-reminder')
                ->and($exception->getMessage())->toContain($method);

            return;
        }

        test()->fail('Expected an InvalidTableAction exception.');
    })->with([
        'to' => [fn (Action $action) => $action->to('/a')->to('/b'), 'to() or route()'],
        'route' => [fn (Action $action) => $action->route('a')->route('b'), 'to() or route()'],
        'to, then route' => [fn (Action $action) => $action->to('/a')->route('b'), 'to() or route()'],
        'confirm' => [fn (Action $action) => $action->confirm()->confirm(title: 'Sure?'), 'confirm()'],
        'destructive' => [fn (Action $action) => $action->destructive()->destructive(false), 'destructive()'],
        'disabled' => [fn (Action $action) => $action->disabled()->disabled(false), 'disabled()'],
        'hidden' => [fn (Action $action) => $action->hidden()->hidden(false), 'hidden()'],
        'icon' => [fn (Action $action) => $action->icon(Icon::DeleteBin)->icon('delete-bin'), 'icon()'],
    ]);

    it('lets each configuration method be called once on the same action', function () {
        withActions(fn () => [
            Action::make('Delete')
                ->to('https://example.com')
                ->confirm()
                ->destructive()
                ->disabled(false)
                ->hidden(false)
                ->icon(Icon::DeleteBin),
        ]);

        expect(rowActions()[0])->toMatchArray(['destructive' => true, 'disabled' => false, 'icon' => 'DeleteBin']);
    });
});

describe('running an action', function () {
    it('runs the handler with the record and redirects back', function () {
        withActions(fn () => [Action::make('Delete', handle: fn (Book $book) => $book->delete())]);
        signIn();

        // Any page the table could be on; it has no route of its own here.
        $this->from('/admin/books') // @phpstan-ignore larasell.preferRouteHelper
            ->post(actionUrl($this->emma, 'delete'))
            ->assertRedirect('/admin/books'); // @phpstan-ignore larasell.preferRouteHelper

        expect(Book::pluck('title')->all())->toBe(['Dune']);
    });

    it('runs a confirmed action without confirmation text', function () {
        withActions(fn () => [Action::make('Delete', handle: fn (Book $book) => $book->delete())->confirm()]);
        signIn();

        $this->post(actionUrl($this->emma, 'delete'))->assertRedirect();

        expect(Book::count())->toBe(1);
    });

    it('requires the confirmation text when the action defines one', function (?string $typed) {
        withActions(fn () => [
            Action::make('Delete', handle: fn (Book $book) => $book->delete())
                ->confirm(confirmationText: fn (Book $book) => $book->title),
        ]);
        signIn();

        $this->post(actionUrl($this->emma, 'delete'), array_filter(['confirmation' => $typed]))
            ->assertSessionHasErrors('confirmation');

        expect(Book::count())->toBe(2);
    })->with([
        'missing' => [null],
        'wrong' => ['Dune'],
        'wrong case' => ['emma'],
    ]);

    it('runs the action when the confirmation text matches', function () {
        withActions(fn () => [
            Action::make('Delete', handle: fn (Book $book) => $book->delete())
                ->confirm(confirmationText: fn (Book $book) => $book->title),
        ]);
        signIn();

        $this->post(actionUrl($this->emma, 'delete'), ['confirmation' => 'Emma'])
            ->assertSessionHasNoErrors();

        expect(Book::pluck('title')->all())->toBe(['Dune']);
    });

    it('returns 404 for actions that cannot run', function (string $url) {
        withActions(fn () => [
            Action::make('View')->to('https://example.com'),
            Action::make('Delete', handle: fn (Book $book) => $book->delete()),
        ]);
        signIn();

        $this->post($url)->assertNotFound();

        expect(Book::count())->toBe(2);
    })->with([
        'unknown action' => fn () => actionUrl($this->emma, 'archive'),
        'link action' => fn () => actionUrl($this->emma, 'view'),
        'unknown record' => '/admin/tables/tables.book-actions-table/999/actions/delete',
        'unknown table' => '/admin/tables/tables.missing-table/1/actions/delete',
        'class that is not a table' => '/admin/tables/book/1/actions/delete',
        'class name instead of slug' => '/admin/tables/Tables.BookActionsTable/1/actions/delete',
    ]);

    it('refuses to run an action that is disabled for the record', function () {
        withActions(fn () => [
            Action::make('Delete', handle: fn (Book $book) => $book->delete())
                ->disabled(fn (Book $book) => $book->title === 'Emma'),
        ]);
        signIn();

        $this->post(actionUrl($this->emma, 'delete'))->assertForbidden();
        $this->post(actionUrl($this->dune, 'delete'))->assertRedirect();

        expect(Book::pluck('title')->all())->toBe(['Emma']);
    });

    it('refuses to run an action that is hidden for the record', function () {
        withActions(fn () => [
            Action::make('Delete', handle: fn (Book $book) => $book->delete())
                ->hidden(fn (Book $book) => $book->title === 'Emma'),
        ]);
        signIn();

        $this->post(actionUrl($this->emma, 'delete'))->assertNotFound();
        $this->post(actionUrl($this->dune, 'delete'))->assertRedirect();

        expect(Book::pluck('title')->all())->toBe(['Emma']);
    });

    it('answers 404, not 403, for an action that is both hidden and disabled', function () {
        withActions(fn () => [
            Action::make('Delete', handle: fn (Book $book) => $book->delete())->hidden()->disabled(),
        ]);
        signIn();

        $this->post(actionUrl($this->emma, 'delete'))->assertNotFound();

        expect(Book::count())->toBe(2);
    });

    it('only runs on records matched by the query returned from model()', function () {
        withActions(fn () => [Action::make('Delete', handle: fn (Book $book) => $book->delete())]);
        signIn();

        $url = fn (Book $book): string => "/admin/tables/tables.frank-herbert-book-actions-table/{$book->id}/actions/delete";

        $this->post($url($this->emma))->assertNotFound();
        $this->post($url($this->dune))->assertRedirect();

        expect(Book::pluck('title')->all())->toBe(['Emma']);
    });

    it('requires a signed-in admin', function () {
        withActions(fn () => [Action::make('Delete', handle: fn (Book $book) => $book->delete())]);

        $this->post(actionUrl($this->emma, 'delete'))->assertRedirect(route('larasell.admin.login'));

        expect(Book::count())->toBe(2);
    });
});

describe('stateful tables', function () {
    it('runs actions on the table made with the same arguments', function () {
        withActions(fn () => [Action::make('Delete', handle: fn (Book $book) => $book->delete())]);
        signIn();
        $janeAusten = json_decode(json_encode(AuthorBookActionsTable::make('Jane Austen')), true);

        expect($janeAusten['rows'])->toHaveCount(1);

        $this->post($janeAusten['rows'][0]['_actions'][0]['url'])->assertRedirect();

        expect(Book::pluck('title')->all())->toBe(['Dune']);
    });

    it('keeps the arguments in the action url encrypted', function () {
        withActions(fn () => [Action::make('Delete', handle: fn (Book $book) => $book->delete())]);
        signIn();
        $url = json_decode(json_encode(AuthorBookActionsTable::make('Frank Herbert')), true)['rows'][0]['_actions'][0]['url'];
        $emma = str_replace("/{$this->dune->id}/", "/{$this->emma->id}/", $url);

        expect(urldecode($url))->not->toContain('Frank Herbert');

        // Jane Austen's book isn't in the Frank Herbert table, and the url's state can't be altered or left out.
        $this->post($emma)->assertNotFound();
        $this->post(preg_replace('/state=[^&]+/', 'state=tampered', $url))->assertNotFound();
        $this->post(preg_replace('/\\?state=[^&]+/', '', $url))->assertNotFound();

        expect(Book::count())->toBe(2);
    });

    it('rejects actions on a table whose arguments it does not remember', function () {
        withActions(fn () => [Action::make('Delete', handle: fn (Book $book) => $book->delete())]);

        json_encode(ForgetfulAuthorBookActionsTable::make('Jane Austen'));
    })->throws(LogicException::class, 'Remember');

    it('still lets a stateful table link without remembering its arguments', function () {
        withActions(fn () => [Action::make('View')->to('https://example.com')]);

        expect(json_decode(json_encode(ForgetfulAuthorBookActionsTable::make('Jane Austen')), true)['rows'][0]['_actions'][0]['url'])
            ->toBe('https://example.com');
    });
});

describe('header actions', function () {
    it('sends an empty list when the table defines none', function () {
        expect(json_decode(json_encode(BooksWithoutActionsTable::make()), true)['headerActions'])->toBe([]);
    });

    it('links to a fixed url', function () {
        expect(headerActions(fn () => [TableAction::make('Add book')->to('/admin/books/create')]))->toBe([
            ['label' => 'Add book', 'method' => 'get', 'url' => '/admin/books/create', 'icon' => null],
        ]);
    });

    it('links to a named route with its parameters', function () {
        Route::get('/books/{shelf}/new', fn () => '')->name('books.create');

        expect(headerActions(fn () => [TableAction::make('Add book')->route('books.create', ['shelf' => 'classics'])])[0]['url'])
            ->toBe(url('/books/classics/new')); // @phpstan-ignore larasell.preferRouteHelper
    });

    it('shows an icon, given as an Icon case or a Remix icon name', function () {
        expect(headerActions(fn () => [
            TableAction::make('Add book')->to('/a')->icon(Icon::Add),
            TableAction::make('Import books')->to('/b')->icon('upload'),
        ]))->sequence(
            fn ($action) => $action->icon->toBe('Add'),
            fn ($action) => $action->icon->toBe('Upload'),
        );
    });

    it('keeps the order the table defines', function () {
        expect(array_column(headerActions(fn () => [
            TableAction::make('Import books')->to('/b'),
            TableAction::make('Add book')->to('/a'),
        ]), 'label'))->toBe(['Import books', 'Add book']);
    });

    it('is sent even when the table has no rows', function () {
        Book::query()->delete();

        expect(headerActions(fn () => [TableAction::make('Add book')->to('/a')]))->toHaveCount(1);
    });

    it('needs a link, and says which table the action belongs to', function () {
        headerActions(fn () => [TableAction::make('Add book')]);
    })->throws(InvalidTableAction::class, 'Table [BookHeaderActionsTable] action [Add book] needs a link');

    it('rejects configuring the same thing twice', function (Closure $configure, string $method) {
        Route::get('/books/new', fn () => '')->name('books.create');

        expect(fn () => $configure(TableAction::make('Add book')))
            ->toThrow(InvalidTableAction::class, "calls {$method} more than once");
    })->with([
        'to() twice' => [fn (TableAction $action) => $action->to('/a')->to('/b'), 'to() or route()'],
        'route() after to()' => [fn (TableAction $action) => $action->to('/a')->route('books.create'), 'to() or route()'],
        'to() after route()' => [fn (TableAction $action) => $action->route('books.create')->to('/a'), 'to() or route()'],
        'icon() twice' => [fn (TableAction $action) => $action->icon(Icon::Add)->icon('upload'), 'icon()'],
    ]);

    it('rejects an empty label', function (string $label) {
        TableAction::make($label);
    })->with(['empty' => '', 'whitespace' => '   '])
        ->throws(InvalidTableAction::class, 'needs a label');

    it('rejects an empty link', function (string $url) {
        TableAction::make('Add book')->to($url);
    })->with(['empty' => '', 'whitespace' => '   '])
        ->throws(InvalidTableAction::class, 'Action [Add book] link cannot be empty');

    it('rejects anything but a list of TableAction', function (Closure $actions, string $expected) {
        expect(fn () => headerActions($actions))->toThrow(function (InvalidTableAction $exception) use ($expected) {
            expect($exception->errorCode())->toBe('larasell-admin.table.action.invalid-list')
                ->and($exception->getMessage())->toBe("Table [BookHeaderActionsTable] headerActions() must be {$expected}.");
        });
    })->with([
        'a row action' => [fn () => [Action::make('Add book')->to('/a')], 'a list of TableAction'],
        'actions keyed by name' => [fn () => ['add' => TableAction::make('Add book')->to('/a')], 'a list'],
    ]);

    it('rejects two actions with the same label', function () {
        headerActions(fn () => [
            TableAction::make('Add book')->to('/a'),
            TableAction::make('Add book')->to('/b'),
        ]);
    })->throws(InvalidTableAction::class, 'Table [BookHeaderActionsTable] headerActions() has more than one action labelled [Add book]');

    it('rejects an unknown icon', function () {
        TableAction::make('Add book')->icon('not-an-icon');
    })->throws(InvalidTableAction::class, 'is not a Remix icon');
});
