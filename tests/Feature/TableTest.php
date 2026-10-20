<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
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

describe('mapped columns', function () {
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
