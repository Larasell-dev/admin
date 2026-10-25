<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\ImageStyle;
use Larasell\Admin\Exceptions\InvalidTableColumn;
use Larasell\Admin\Tables\Attributes\UseModel;
use Larasell\Admin\Tables\Columns\Image;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Admin\Tests\Fixtures\Book;
use Larasell\Admin\Tests\Fixtures\Models;
use Larasell\Admin\Tests\Fixtures\Tables;
use Larasell\Admin\Tests\Fixtures\Tables\Library\AudioBookTable;

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
        ['id' => $dune->id, 'title' => 'Dune', 'author_name' => 'Frank Herbert', '_actions' => [], '_link' => null, '_images' => []],
        ['id' => $emma->id, 'title' => 'Emma', 'author_name' => 'Jane Austen', '_actions' => [], '_link' => null, '_images' => []],
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

it('derives a sentence case title from the class name without its Table suffix', function () {
    expect(tableResource(BookAuthorsTable::make())['title'])->toBe('Book authors')
        ->and(Tables\Books::make()->title())->toBe('Books');
});

it('returns no rows when there are no models', function () {
    expect(tableResource(BooksTable::make())['rows'])->toBe([]);
});

it('lists the model declared with the UseModel attribute', function () {
    $dune = Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

    expect(tableResource(AttributedBooksTable::make())['rows'])->toBe([
        ['id' => $dune->id, 'title' => 'Dune', '_actions' => [], '_link' => null, '_images' => []],
    ]);
});

describe('inferred model', function () {
    it('lists the model named like the table in the Models namespace', function () {
        $dune = Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

        expect(tableResource(Tables\Book::make())['rows'])->toBe([
            ['id' => $dune->id, 'title' => 'Dune', '_actions' => [], '_link' => null, '_images' => []],
        ])->and(Tables\Book::make()->findRecord((string) $dune->id))->toBeInstanceOf(Models\Book::class);
    });

    it('keeps the sub-namespace and ignores a Table suffix', function () {
        $dune = Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

        expect(AudioBookTable::make()->findRecord((string) $dune->id))->toBeInstanceOf(Models\Library\AudioBook::class);
    });

    it('does not infer the singular model from a plural name, and names the model it looked for', function () {
        expect(fn () => tableResource(Tables\Books::make()))
            ->toThrow(LogicException::class, '[Larasell\Admin\Tests\Fixtures\Models\Books]');
    });
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
    })->with(['_actions', '_link', '_images']);

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

/**
 * A table whose title column shows the image the given callback configures, next to an author column without one.
 */
function imageTitleTable(Closure $image): Table
{
    ImageTitleTable::$image = $image;

    return ImageTitleTable::make();
}

class ImageTitleTable extends Table
{
    public static Closure $image;

    protected ?string $model = Book::class;

    public function columns(): array
    {
        return [TextColumn::make('title')->image(static::$image), TextColumn::make('author_name')];
    }
}

/**
 * The title column's image of each row, keyed by title.
 *
 * @return array<string, array<string, string>|null>
 */
function titleImages(Closure $image): array
{
    $rows = tableResource(imageTitleTable($image))['rows'];

    return array_combine(array_column($rows, 'title'), array_map(fn (array $row): ?array => $row['_images']['title'], $rows));
}

/**
 * Runs the callback and returns the InvalidTableColumn it throws, failing the test when it throws none.
 */
function invalidColumn(Closure $callback): InvalidTableColumn
{
    try {
        $callback();
    } catch (InvalidTableColumn $exception) {
        return $exception;
    }

    test()->fail('Expected an InvalidTableColumn exception.');
}

describe('column images', function () {
    beforeEach(function () {
        Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
        Book::create(['title' => 'Beowulf', 'author_name' => 'Anonymous']);
    });

    it('shows the icon the callback picks for each record, by its case name', function () {
        expect(titleImages(fn (Book $book, Image $image) => $image->icon($book->author_name === 'Anonymous' ? Icon::QuestionMark : Icon::QuillPen)))->toBe([
            'Dune' => ['type' => 'icon', 'icon' => 'QuillPen'],
            'Beowulf' => ['type' => 'icon', 'icon' => 'QuestionMark'],
        ]);
    });

    it('takes an icon by its Remix icon name', function () {
        expect(titleImages(fn (Book $book, Image $image) => $image->icon('book-open'))['Dune'])
            ->toBe(['type' => 'icon', 'icon' => 'BookOpen']);
    });

    it('rejects an icon name that is not a Remix icon', function (string $icon, string $code) {
        $exception = invalidColumn(fn () => tableResource(imageTitleTable(fn (Book $book, Image $image) => $image->icon($icon))));

        expect($exception->errorCode())->toBe($code)
            ->and($exception->getMessage())->toContain('[title]');
    })->with([
        'empty' => ['  ', 'larasell-admin.table.column.empty-image-icon'],
        'style variant' => ['book-open-line', 'larasell-admin.table.column.image-icon-style-variant'],
        'unknown' => ['BookOpen', 'larasell-admin.table.column.unknown-image-icon'],
    ]);

    it('shows the image at the url the callback picks for each record', function () {
        expect(titleImages(fn (Book $book, Image $image) => $image->url("https://covers.test/{$book->title}.jpg")))->toBe([
            'Dune' => ['type' => 'url', 'url' => 'https://covers.test/Dune.jpg', 'style' => 'square'],
            'Beowulf' => ['type' => 'url', 'url' => 'https://covers.test/Beowulf.jpg', 'style' => 'square'],
        ]);
    });

    it('takes the url with to()', function () {
        expect(titleImages(fn (Book $book, Image $image) => $image->to('https://covers.test/dune.jpg'))['Dune']['url'])
            ->toBe('https://covers.test/dune.jpg');
    });

    it('rejects an empty url', function () {
        $exception = invalidColumn(fn () => tableResource(imageTitleTable(fn (Book $book, Image $image) => $image->url(' '))));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.empty-image-url')
            ->and($exception->getMessage())->toContain('[title]');
    });

    describe('routes', function () {
        beforeEach(function () {
            Route::get('covers/{book}', fn () => '')->name('books.cover');
            Route::getRoutes()->refreshNameLookups();
        });

        it('builds the url of a named route', function () {
            $dune = Book::where('title', 'Dune')->sole();

            expect(titleImages(fn (Book $book, Image $image) => $image->route('books.cover', $book))['Dune']['url'])
                ->toBe("http://localhost/covers/{$dune->id}");
        });

        it('builds a relative url of a named route', function () {
            $dune = Book::where('title', 'Dune')->sole();

            expect(titleImages(fn (Book $book, Image $image) => $image->route('books.cover', $book, absolute: false))['Dune']['url'])
                ->toBe("/covers/{$dune->id}");
        });

        it('builds a signed url of a named route', function () {
            $url = titleImages(fn (Book $book, Image $image) => $image->signedRoute('books.cover', $book))['Dune']['url'];

            expect(URL::hasValidSignature(Request::create($url)))->toBeTrue()
                ->and($url)->not->toContain('expires=');
        });

        it('builds a signed url of a named route that expires', function () {
            $this->travelTo('2026-10-20 12:00:00');
            $url = titleImages(fn (Book $book, Image $image) => $image->temporarySignedRoute('books.cover', now()->addHour(), $book))['Dune']['url'];

            $this->travelTo('2026-10-20 12:59:00');
            expect(URL::hasValidSignature(Request::create($url)))->toBeTrue();

            $this->travelTo('2026-10-20 13:01:00');
            expect(URL::hasValidSignature(Request::create($url)))->toBeFalse();
        });
    });

    it('shows a picture round or square', function () {
        $cover = 'https://covers.test/dune.jpg';

        expect(titleImages(fn (Book $book, Image $image) => $image->url($cover)->rounded())['Dune']['style'])->toBe('round')
            ->and(titleImages(fn (Book $book, Image $image) => $image->url($cover)->style(ImageStyle::Round))['Dune']['style'])->toBe('round')
            ->and(titleImages(fn (Book $book, Image $image) => $image->url($cover)->style(ImageStyle::Square))['Dune']['style'])->toBe('square');
    });

    it('rejects giving an icon a style, which only pictures have', function (Closure $callback) {
        $exception = invalidColumn(fn () => tableResource(imageTitleTable($callback)));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.styled-image-icon')
            ->and($exception->getMessage())->toContain('[title]');
    })->with([
        'rounded() after icon()' => [fn (Book $book, Image $image) => $image->icon(Icon::Book)->rounded()],
        'style() before icon()' => [fn (Book $book, Image $image) => $image->style(ImageStyle::Square)->icon(Icon::Book)],
    ]);

    it('sends the alt text and title of each record\'s image', function () {
        expect(titleImages(fn (Book $book, Image $image) => $image->url("https://covers.test/{$book->title}.jpg")->alt("Cover of {$book->title}")->title($book->author_name)))->toBe([
            'Dune' => ['type' => 'url', 'url' => 'https://covers.test/Dune.jpg', 'style' => 'square', 'alt' => 'Cover of Dune', 'title' => 'Frank Herbert'],
            'Beowulf' => ['type' => 'url', 'url' => 'https://covers.test/Beowulf.jpg', 'style' => 'square', 'alt' => 'Cover of Beowulf', 'title' => 'Anonymous'],
        ]);
    });

    it('takes an alt text and title for an icon too', function () {
        expect(titleImages(fn (Book $book, Image $image) => $image->icon(Icon::QuillPen)->title('Has an author'))['Dune'])
            ->toBe(['type' => 'icon', 'icon' => 'QuillPen', 'title' => 'Has an author']);
    });

    it('rejects giving an image a second alt text or title', function (Closure $callback, string $method) {
        $exception = invalidColumn(fn () => tableResource(imageTitleTable($callback)));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.configured-twice')
            ->and($exception->getMessage())->toContain($method);
    })->with([
        'two alts' => [fn (Book $book, Image $image) => $image->icon(Icon::Book)->alt('a')->alt('b'), 'alt()'],
        'two titles' => [fn (Book $book, Image $image) => $image->icon(Icon::Book)->title('a')->title('b'), 'title()'],
    ]);

    it('shows no image for the records the callback returns null for', function () {
        expect(titleImages(fn (Book $book, Image $image) => $book->title === 'Dune' ? $image->icon(Icon::Book) : null))->toBe([
            'Dune' => ['type' => 'icon', 'icon' => 'Book'],
            'Beowulf' => null,
        ]);
    });

    it('lists only the columns that have an image', function () {
        expect(tableResource(imageTitleTable(fn (Book $book, Image $image) => $image->icon(Icon::Book)))['rows'][0]['_images'])
            ->toHaveKeys(['title'])
            ->not->toHaveKey('author_name');
    });

    it('rejects a callback that returns something other than the image', function (Closure $callback, string $type) {
        $exception = invalidColumn(fn () => tableResource(imageTitleTable($callback)));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.invalid-image')
            ->and($exception->getMessage())->toContain('[title]')
            ->and($exception->getMessage())->toContain($type);
    })->with([
        'a url' => [fn () => 'https://covers.test/dune.jpg', 'string'],
        'an icon' => [fn () => Icon::Book, Icon::class],
    ]);

    it('rejects an image without an icon or a url', function () {
        $exception = invalidColumn(fn () => tableResource(imageTitleTable(fn (Book $book, Image $image) => $image->rounded())));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.image-without-source')
            ->and($exception->getMessage())->toContain('[title]');
    });

    it('rejects giving an image a second source or style', function (Closure $callback, string $method) {
        $exception = invalidColumn(fn () => tableResource(imageTitleTable($callback)));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.configured-twice')
            ->and($exception->getMessage())->toContain('[title]')
            ->and($exception->getMessage())->toContain($method);
    })->with([
        'icon and url' => [fn (Book $book, Image $image) => $image->icon(Icon::Book)->url('https://covers.test/dune.jpg'), 'icon(), url() or a route method'],
        'two urls' => [fn (Book $book, Image $image) => $image->to('https://covers.test/a.jpg')->url('https://covers.test/b.jpg'), 'icon(), url() or a route method'],
        'two styles' => [fn (Book $book, Image $image) => $image->url('https://covers.test/dune.jpg')->rounded()->style(ImageStyle::Square), 'rounded() or style()'],
    ]);

    it('rejects a callback that changes the record', function () {
        $exception = invalidColumn(fn () => tableResource(imageTitleTable(function (Book $book, Image $image): Image {
            $book->title = 'Changed';

            return $image->icon(Icon::Book);
        })));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.record-modified')
            ->and($exception->getMessage())->toContain('[title]')
            ->and($exception->getMessage())->toContain('image()');
    });

    it('rejects calling image() twice on a column', function () {
        $exception = invalidColumn(fn () => TextColumn::make('title')->image(fn () => null)->image(fn () => null));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.configured-twice')
            ->and($exception->getMessage())->toContain('image()');
    });

    it('keeps the column text next to the image', function () {
        expect(tableResource(imageTitleTable(fn (Book $book, Image $image) => $image->icon(Icon::Book)))['rows'][0])
            ->toMatchArray(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
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
