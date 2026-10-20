<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Larasell\Admin\Exceptions\InvalidTableColumn;
use Larasell\Admin\Tables\Attributes\UseModel;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Admin\Tests\Fixtures\Book;

class BooksTable extends Table
{
    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [
            TextColumn::make('title'),
            TextColumn::make('author_name', 'Written by'),
        ];
    }
}

class BookAuthorsTable extends Table
{
    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [
            TextColumn::make('author_name'),
        ];
    }
}

#[UseModel(Book::class)]
class AttributedBooksTable extends Table
{
    public function columns(): array
    {
        return [
            TextColumn::make('title'),
        ];
    }
}

class LinkedBooksTable extends Table
{
    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [
            TextColumn::make('title'),
        ];
    }

    public function rowLink(Book $book): ?string
    {
        if ($book->author_name === 'Anonymous') {
            return null;
        }

        return "https://books.test/{$book->id}";
    }
}

class MappedBooksTable extends Table
{
    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [
            TextColumn::make('summary')
                ->map(fn ($value, Book $book): string => "{$book->title} by {$book->author_name}"),
            TextColumn::make('created_at', 'Added')
                ->map(fn ($value) => $value?->diffForHumans()),
        ];
    }
}

/**
 * A table whose only column maps the title with the given callback.
 */
function mappedTitleTable(Closure $map): Table
{
    MappedTitleTable::$map = $map;

    return MappedTitleTable::make();
}

class MappedTitleTable extends Table
{
    public static Closure $map;

    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [TextColumn::make('title')->map(static::$map)];
    }
}

/**
 * @param  array<string, mixed>  $query  The request's query string, e.g. ['page' => 2].
 */
function tableResource(Table $table, array $query = []): array
{
    app()->instance('request', Request::create('/admin/tables/books', 'GET', $query));

    return json_decode(json_encode($table), true);
}

function createBooks(int $count): void
{
    foreach (range(1, $count) as $number) {
        Book::create(['title' => "Book {$number}", 'author_name' => "Author {$number}"]);
    }
}

beforeEach(function () {
    Book::createTable();
});

it('describes its columns with a label derived from the column name', function () {
    expect(tableResource(BooksTable::make())['columns'])->toBe([
        ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
        ['name' => 'author_name', 'label' => 'Written by', 'type' => 'text'],
    ]);
});

it('turns multi-word column names into a sentence case label', function () {
    expect(tableResource(BookAuthorsTable::make())['columns'][0]['label'])->toBe('Author name');
});

it('returns a row per model with the value of each column', function () {
    $dune = Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
    $emma = Book::create(['title' => 'Emma', 'author_name' => 'Jane Austen']);

    expect(tableResource(BooksTable::make())['rows'])->toBe([
        ['id' => $dune->id, 'title' => 'Dune', 'author_name' => 'Frank Herbert', '_actions' => [], '_link' => null],
        ['id' => $emma->id, 'title' => 'Emma', 'author_name' => 'Jane Austen', '_actions' => [], '_link' => null],
    ]);
});

it('links each row to the url returned by rowLink, or not at all when it returns null', function () {
    $dune = Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
    Book::create(['title' => 'Beowulf', 'author_name' => 'Anonymous']);
    $emma = Book::create(['title' => 'Emma', 'author_name' => 'Jane Austen']);

    expect(array_column(tableResource(LinkedBooksTable::make())['rows'], '_link', 'title'))->toBe([
        'Dune' => "https://books.test/{$dune->id}",
        'Beowulf' => null,
        'Emma' => "https://books.test/{$emma->id}",
    ]);
});

it('derives a sentence case title from the class name', function () {
    expect(tableResource(BookAuthorsTable::make())['title'])->toBe('Book authors table');
});

it('returns no rows when there are no models', function () {
    expect(tableResource(BooksTable::make())['rows'])->toBe([]);
});

it('lists the model declared with the UseModel attribute', function () {
    $dune = Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

    expect(tableResource(AttributedBooksTable::make())['rows'])->toBe([
        ['id' => $dune->id, 'title' => 'Dune', '_actions' => [], '_link' => null],
    ]);
});

class FrankHerbertBooksTable extends Table
{
    /**
     * @return Builder<Book>
     */
    protected function model(): Builder
    {
        return Book::where('author_name', 'Frank Herbert');
    }

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }
}

class BooksByTitleTable extends Table
{
    /**
     * @return Builder<Book>
     */
    protected function model(): Builder
    {
        return Book::orderBy('title');
    }

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }
}

class BooksLikeDuneTable extends Table
{
    /**
     * @return HasMany<Book, Book>
     */
    protected function model(): HasMany
    {
        return Book::where('title', 'Dune')->firstOrFail()->booksBySameAuthor();
    }

    public function columns(): array
    {
        return [TextColumn::make('title')];
    }
}

it('lists only the records matched by the query returned from model()', function () {
    Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
    Book::create(['title' => 'Emma', 'author_name' => 'Jane Austen']);
    Book::create(['title' => 'Dune Messiah', 'author_name' => 'Frank Herbert']);

    $table = tableResource(FrankHerbertBooksTable::make());

    expect(array_column($table['rows'], 'title'))->toBe(['Dune', 'Dune Messiah'])
        ->and($table['pagination']['total'])->toBe(2);
});

it('keeps the order of the query returned from model()', function () {
    Book::create(['title' => 'Emma', 'author_name' => 'Jane Austen']);
    Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

    expect(array_column(tableResource(BooksByTitleTable::make())['rows'], 'title'))->toBe(['Dune', 'Emma']);
});

it('lists the records of a relationship returned from model()', function () {
    Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
    Book::create(['title' => 'Emma', 'author_name' => 'Jane Austen']);
    Book::create(['title' => 'Dune Messiah', 'author_name' => 'Frank Herbert']);

    $table = tableResource(BooksLikeDuneTable::make());

    expect(array_column($table['rows'], 'title'))->toBe(['Dune', 'Dune Messiah'])
        ->and($table['pagination']['total'])->toBe(2);
});

class ColumnsTable extends Table
{
    /** @var array<int, TextColumn> */
    public static array $definedColumns = [];

    protected ?string $model = Book::class;

    public function columns(): array
    {
        return static::$definedColumns;
    }
}

/**
 * @param  array<int, TextColumn>  $columns
 */
function tableWithColumns(array $columns): Table
{
    ColumnsTable::$definedColumns = $columns;

    return ColumnsTable::make();
}

describe('column names', function () {
    it('rejects two columns with the same name', function () {
        try {
            tableResource(tableWithColumns([
                TextColumn::make('title'),
                TextColumn::make('author_name'),
                TextColumn::make('title', 'Headline')->map(fn ($value) => strtoupper($value)),
            ]));
        } catch (InvalidTableColumn $exception) {
            expect($exception->errorCode())->toBe('larasell-admin.table.column.duplicate-name')
                ->and($exception->getMessage())->toContain('[title]')
                ->and($exception->getMessage())->not->toContain('author_name');

            return;
        }

        test()->fail('Expected an InvalidTableColumn exception.');
    });

    it('rejects a column named like a key the table adds to every row', function (string $name) {
        try {
            tableResource(tableWithColumns([TextColumn::make('title'), TextColumn::make($name)]));
        } catch (InvalidTableColumn $exception) {
            expect($exception->errorCode())->toBe('larasell-admin.table.column.reserved-name')
                ->and($exception->getMessage())->toContain("[{$name}]");

            return;
        }

        test()->fail('Expected an InvalidTableColumn exception.');
    })->with(['_actions', '_link']);

    it('rejects column names before there are any rows', function () {
        tableResource(tableWithColumns([TextColumn::make('title'), TextColumn::make('title')]));
    })->throws(InvalidTableColumn::class);

    it('allows an id column', function () {
        $dune = Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

        expect(tableResource(tableWithColumns([TextColumn::make('id', 'ID'), TextColumn::make('title')]))['rows'][0])
            ->toMatchArray(['id' => (string) $dune->id, 'title' => 'Dune']);
    });
});

describe('mapped columns', function () {
    it('rejects a callback that changes the record', function () {
        Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

        try {
            tableResource(new class extends Table
            {
                protected ?string $model = Book::class;

                public function columns(): array
                {
                    return [TextColumn::make('title')->map(function ($value, Book $book): string {
                        $book->author_name = 'Someone else';

                        return $value;
                    })];
                }
            });
        } catch (InvalidTableColumn $exception) {
            expect($exception->errorCode())->toBe('larasell-admin.table.column.record-modified')
                ->and($exception->getMessage())->toContain('[title]')
                ->and($exception->getMessage())->toContain('author_name');

            return;
        }

        test()->fail('Expected an InvalidTableColumn exception.');
    });

    it('allows a callback that sets an attribute back to its original value', function () {
        Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

        expect(tableResource(new class extends Table
        {
            protected ?string $model = Book::class;

            public function columns(): array
            {
                return [TextColumn::make('title')->map(function ($value, Book $book): string {
                    $book->title = $value;

                    return $value;
                })];
            }
        })['rows'][0]['title'])->toBe('Dune');
    });

    it('computes a column that is not an attribute from the record', function () {
        Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

        expect(tableResource(MappedBooksTable::make())['rows'][0]['summary'])->toBe('Dune by Frank Herbert');
    });

    it('maps the cast value of an attribute', function () {
        $this->travelTo('2026-10-20 12:00:00');
        Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
        $this->travelTo('2026-10-20 14:00:00');

        expect(tableResource(MappedBooksTable::make())['rows'][0]['created_at'])->toBe('2 hours ago');
    });

    it('computes a column even when models forbid accessing missing attributes', function () {
        Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
        Model::preventAccessingMissingAttributes();

        try {
            expect(tableResource(MappedBooksTable::make())['rows'][0]['summary'])->toBe('Dune by Frank Herbert');
        } finally {
            Model::preventAccessingMissingAttributes(false);
        }
    });

    it('still rejects a missing attribute on an unmapped column when models forbid it', function () {
        Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
        Model::preventAccessingMissingAttributes();

        try {
            expect(fn () => tableResource(new class extends Table
            {
                protected ?string $model = Book::class;

                public function columns(): array
                {
                    return [TextColumn::make('summary')];
                }
            }))->toThrow(MissingAttributeException::class);
        } finally {
            Model::preventAccessingMissingAttributes(false);
        }
    });

    it('does not expose the mapping in the column description', function () {
        expect(tableResource(MappedBooksTable::make())['columns'])->toBe([
            ['name' => 'summary', 'label' => 'Summary', 'type' => 'text'],
            ['name' => 'created_at', 'label' => 'Added', 'type' => 'text'],
        ]);
    });

    it('rejects mapping a column twice', function () {
        try {
            TextColumn::make('summary')->map(fn () => 'first')->map(fn () => 'second');
        } catch (InvalidTableColumn $exception) {
            expect($exception->errorCode())->toBe('larasell-admin.table.column.configured-twice')
                ->and($exception->getMessage())->toContain('summary')
                ->and($exception->getMessage())->toContain('map()');

            return;
        }

        test()->fail('Expected an InvalidTableColumn exception.');
    });
});

describe('mapped text columns', function () {
    it('shows a number or a Stringable the callback returns as text', function (mixed $result, string $text) {
        Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

        expect(tableResource(mappedTitleTable(fn () => $result))['rows'][0]['title'])->toBe($text);
    })->with([
        'int' => [42, '42'],
        'float' => [4.5, '4.5'],
        'Stringable' => [str('Dune')->upper(), 'DUNE'],
    ]);

    it('rejects a callback result that is not text', function (mixed $result, string $type) {
        Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

        try {
            tableResource(mappedTitleTable(fn () => $result));
        } catch (InvalidTableColumn $exception) {
            expect($exception->errorCode())->toBe('larasell-admin.table.column.invalid-mapped-value')
                ->and($exception->getMessage())->toContain('title')
                ->and($exception->getMessage())->toContain($type);

            return;
        }

        test()->fail('Expected an InvalidTableColumn exception.');
    })->with([
        'true' => [true, 'bool'],
        'false' => [false, 'bool'],
        'array' => [['Dune'], 'array'],
        'object without __toString' => [new stdClass, 'stdClass'],
    ]);
});

describe('pagination', function () {
    it('shows the first 25 rows and the total across all pages', function () {
        createBooks(30);

        $resource = tableResource(BooksTable::make());

        expect(array_column($resource['rows'], 'title'))->toBe(array_map(fn (int $n): string => "Book {$n}", range(1, 25)))
            ->and($resource['pagination'])->toBe(['page' => 1, 'pageSize' => 25, 'total' => 30]);
    });

    it('shows the requested page', function () {
        createBooks(30);

        $resource = tableResource(BooksTable::make(), ['page' => 2]);

        expect(array_column($resource['rows'], 'title'))->toBe(['Book 26', 'Book 27', 'Book 28', 'Book 29', 'Book 30'])
            ->and($resource['pagination'])->toBe(['page' => 2, 'pageSize' => 25, 'total' => 30]);
    });

    it('shows as many rows per page as requested', function (int $pageSize) {
        createBooks(101);

        $resource = tableResource(BooksTable::make(), ['per_page' => $pageSize]);

        expect($resource['rows'])->toHaveCount($pageSize)
            ->and($resource['pagination'])->toBe(['page' => 1, 'pageSize' => $pageSize, 'total' => 101]);
    })->with([50, 100]);

    it('falls back to 25 rows per page for a page size it does not offer', function (mixed $pageSize) {
        createBooks(30);

        $resource = tableResource(BooksTable::make(), ['per_page' => $pageSize]);

        expect($resource['rows'])->toHaveCount(25)
            ->and($resource['pagination']['pageSize'])->toBe(25);
    })->with([10, 1000, 0, -50, 'all']);

    it('shows the last page when the requested page is past it', function () {
        createBooks(30);

        $resource = tableResource(BooksTable::make(), ['page' => 9]);

        expect(array_column($resource['rows'], 'title'))->toBe(['Book 26', 'Book 27', 'Book 28', 'Book 29', 'Book 30'])
            ->and($resource['pagination'])->toBe(['page' => 2, 'pageSize' => 25, 'total' => 30]);
    });
});
