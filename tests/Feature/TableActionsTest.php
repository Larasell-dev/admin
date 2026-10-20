<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidTableAction;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Admin\Tests\Fixtures\AudioBook;
use Larasell\Admin\Tests\Fixtures\Book;
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

function withActions(Closure $actions): void
{
    BookActionsTable::$actions = $actions;
}

/**
 * @return array<int, array<string, mixed>>
 */
function rowActions(int $row = 0): array
{
    return json_decode(json_encode(BookActionsTable::make()), true)['rows'][$row]['_actions'];
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
    config(['larasell-admin.tables.namespace' => 'Larasell\\Admin\\Tests\\Fixtures']);
    BookActionsTable::$bookModel = Book::class;
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

        expect(rowActions(1)[0]['url'])->toBe(url("/books/{$this->emma->id}"));
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
        BookActionsTable::$bookModel = AudioBook::class;
        withActions(fn () => [Action::make('Send Reminder', handle: fn (AudioBook $book) => null)->confirm()]);

        expect(rowActions()[0]['confirm'])->toMatchArray([
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
        'empty icon' => ['', 'larasell-admin.table.action.unknown-icon'],
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
});

describe('running an action', function () {
    it('runs the handler with the record and redirects back', function () {
        withActions(fn () => [Action::make('Delete', handle: fn (Book $book) => $book->delete())]);
        signIn();

        $this->from('/admin/books')
            ->post(actionUrl($this->emma, 'delete'))
            ->assertRedirect('/admin/books');

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

    it('requires a signed-in admin', function () {
        withActions(fn () => [Action::make('Delete', handle: fn (Book $book) => $book->delete())]);

        $this->post(actionUrl($this->emma, 'delete'))->assertRedirect('/admin/login');

        expect(Book::count())->toBe(2);
    });
});
