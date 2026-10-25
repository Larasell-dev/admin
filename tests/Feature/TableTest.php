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
use Larasell\Admin\Enums\Table\ImageStyle;
use Larasell\Admin\Enums\Table\Variant;
use Larasell\Admin\Exceptions\InvalidTableColumn;
use Larasell\Admin\Tables\Attributes\UseModel;
use Larasell\Admin\Tables\Columns\BadgeColumn;
use Larasell\Admin\Tables\Columns\Column;
use Larasell\Admin\Tables\Columns\IconImage;
use Larasell\Admin\Tables\Columns\Image;
use Larasell\Admin\Tables\Columns\PictureImage;
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
        ['id' => $dune->id, 'title' => 'Dune', 'author_name' => 'Frank Herbert', '_actions' => [], '_link' => null, '_images' => [], '_variants' => []],
        ['id' => $emma->id, 'title' => 'Emma', 'author_name' => 'Jane Austen', '_actions' => [], '_link' => null, '_images' => [], '_variants' => []],
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
        ['id' => $dune->id, 'title' => 'Dune', '_actions' => [], '_link' => null, '_images' => [], '_variants' => []],
    ]);
});

describe('inferred model', function () {
    it('lists the model named like the table in the Models namespace', function () {
        $dune = Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);

        expect(tableResource(Tables\Book::make())['rows'])->toBe([
            ['id' => $dune->id, 'title' => 'Dune', '_actions' => [], '_link' => null, '_images' => [], '_variants' => []],
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
    /** @var array<int, Column> */
    public static array $definedColumns = [];

    protected ?string $model = Book::class;

    public function columns(): array
    {
        return static::$definedColumns;
    }
}

/**
 * @param  array<int, Column>  $columns
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
    })->with(['_actions', '_link', '_images', '_variants']);

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
            'Dune' => ['type' => 'icon', 'icon' => 'QuillPen', 'style' => null, 'alt' => null, 'title' => null],
            'Beowulf' => ['type' => 'icon', 'icon' => 'QuestionMark', 'style' => null, 'alt' => null, 'title' => null],
        ]);
    });

    it('takes an icon by its Remix icon name', function () {
        expect(titleImages(fn (Book $book, Image $image) => $image->icon('book-open'))['Dune'])
            ->toBe(['type' => 'icon', 'icon' => 'BookOpen', 'style' => null, 'alt' => null, 'title' => null]);
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
            'Dune' => ['type' => 'url', 'url' => 'https://covers.test/Dune.jpg', 'style' => 'square', 'alt' => null, 'title' => null],
            'Beowulf' => ['type' => 'url', 'url' => 'https://covers.test/Beowulf.jpg', 'style' => 'square', 'alt' => null, 'title' => null],
        ]);
    });

    it('takes the url with to()', function () {
        expect(titleImages(fn (Book $book, Image $image) => $image->to('https://covers.test/dune.jpg'))['Dune']['url'])
            ->toBe('https://covers.test/dune.jpg');
    });

    it('takes a url a browser can show as a picture', function (string $url) {
        expect(titleImages(fn (Book $book, Image $image) => $image->url($url))['Dune']['url'])->toBe($url);
    })->with([
        'https' => 'https://covers.test/dune.jpg',
        'http' => 'http://covers.test/dune.jpg',
        'uppercase scheme' => 'HTTPS://covers.test/dune.jpg',
        'root-relative' => '/covers/dune.jpg',
        'path-relative' => 'covers/dune.jpg',
        'protocol-relative' => '//covers.test/dune.jpg',
        'colon in the path' => '/covers/dune:messiah.jpg',
        'data image' => 'data:image/png;base64,iVBORw0KGgo=',
        'data svg image' => 'data:image/svg+xml,%3Csvg%2F%3E',
    ]);

    it('rejects a url with a scheme that is not a picture', function (string $url) {
        $exception = invalidColumn(fn () => tableResource(imageTitleTable(fn (Book $book, Image $image) => $image->url($url))));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.unsupported-image-url')
            ->and($exception->getMessage())->toContain('[title]');
    })->with([
        'javascript' => 'javascript:alert(1)',
        'javascript, mixed case' => 'JavaScript:alert(1)',
        // Browsers drop leading spaces and control characters, and tabs and newlines anywhere, before reading the scheme.
        'javascript, leading space' => ' javascript:alert(1)',
        'javascript, tab in the scheme' => "java\tscript:alert(1)",
        'vbscript' => 'vbscript:msgbox(1)',
        'data that is not an image' => 'data:text/html,<script>alert(1)</script>',
        'file' => 'file:///etc/passwd',
        'ftp' => 'ftp://covers.test/dune.jpg',
        'blob' => 'blob:https://covers.test/0b6e',
        'mailto' => 'mailto:frank@herbert.test',
    ]);

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

    // A style on an icon is then an undefined method, which IDEs and PHPStan flag as it is written.
    it('gives only pictures a style, so an icon cannot take one', function () {
        $images = [];
        titleImages(function (Book $book, Image $image) use (&$images): IconImage|PictureImage {
            $images['factory'] = $image;

            return $images[$book->title] = $book->title === 'Dune' ? $image->icon(Icon::Book) : $image->url('https://covers.test/beowulf.jpg');
        });

        expect($images['Dune'])->toBeInstanceOf(IconImage::class)
            ->and($images['Beowulf'])->toBeInstanceOf(PictureImage::class)
            ->and(array_map(fn (string $method): array => [
                method_exists($images['factory'], $method),
                method_exists($images['Dune'], $method),
                method_exists($images['Beowulf'], $method),
            ], ['rounded', 'style']))->toBe([[false, false, true], [false, false, true]]);
    });

    it('sends the alt text and title of each record\'s image', function () {
        expect(titleImages(fn (Book $book, Image $image) => $image->url("https://covers.test/{$book->title}.jpg")->alt("Cover of {$book->title}")->title($book->author_name)))->toBe([
            'Dune' => ['type' => 'url', 'url' => 'https://covers.test/Dune.jpg', 'style' => 'square', 'alt' => 'Cover of Dune', 'title' => 'Frank Herbert'],
            'Beowulf' => ['type' => 'url', 'url' => 'https://covers.test/Beowulf.jpg', 'style' => 'square', 'alt' => 'Cover of Beowulf', 'title' => 'Anonymous'],
        ]);
    });

    it('takes an alt text and title for an icon too', function () {
        expect(titleImages(fn (Book $book, Image $image) => $image->icon(Icon::QuillPen)->title('Has an author'))['Dune'])
            ->toBe(['type' => 'icon', 'icon' => 'QuillPen', 'style' => null, 'alt' => null, 'title' => 'Has an author']);
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
            'Dune' => ['type' => 'icon', 'icon' => 'Book', 'style' => null, 'alt' => null, 'title' => null],
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
        'the Image, without an icon or a url' => [fn (Book $book, Image $image) => $image, Image::class],
    ]);

    it('rejects giving an image a second source or style', function (Closure $callback, string $method) {
        $exception = invalidColumn(fn () => tableResource(imageTitleTable($callback)));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.configured-twice')
            ->and($exception->getMessage())->toContain('[title]')
            ->and($exception->getMessage())->toContain($method);
    })->with([
        // Only one of the two images could be returned, so the other would silently not show.
        'icon and url' => [fn (Book $book, Image $image) => [$image->icon(Icon::Book), $image->url('https://covers.test/dune.jpg')][1], 'icon(), url() or a route method'],
        'two urls' => [fn (Book $book, Image $image) => [$image->to('https://covers.test/a.jpg'), $image->url('https://covers.test/b.jpg')][1], 'icon(), url() or a route method'],
        'two styles' => [fn (Book $book, Image $image) => $image->url('https://covers.test/dune.jpg')->rounded()->style(ImageStyle::Square), 'rounded() or style()'],
    ]);

    it('rejects a callback that changes the record', function () {
        $exception = invalidColumn(fn () => tableResource(imageTitleTable(function (Book $book, Image $image): IconImage {
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

enum BookAuthor: string
{
    case FrankHerbert = 'Frank Herbert';
    case Anonymous = 'Anonymous';
    case JaneAusten = 'Jane Austen';
}

/**
 * A book whose author is cast to an enum.
 */
class AuthorCastBook extends Book
{
    protected $table = 'books';

    protected $casts = ['author_name' => BookAuthor::class];
}

/**
 * A book whose author is cast to the type in $cast, e.g. to turn a column that worked with a map into one it can't match.
 */
class AuthorRecastBook extends Book
{
    public static string $cast = 'string';

    protected $table = 'books';

    protected function casts(): array
    {
        return ['author_name' => static::$cast];
    }
}

class AuthorRecastBooksTable extends Table
{
    public static BadgeColumn $badge;

    protected ?string $model = AuthorRecastBook::class;

    public function columns(): array
    {
        return [TextColumn::make('title'), static::$badge];
    }
}

class AuthorCastBooksTable extends Table
{
    public static BadgeColumn $badge;

    protected ?string $model = AuthorCastBook::class;

    public function columns(): array
    {
        return [TextColumn::make('title'), static::$badge];
    }
}

/**
 * The badge variant of each row's author column, keyed by title.
 *
 * @return array<string, string>
 */
function authorVariants(BadgeColumn $badge, ?Table $table = null): array
{
    $rows = tableResource($table ?? tableWithColumns([TextColumn::make('title'), $badge]))['rows'];

    return array_combine(array_column($rows, 'title'), array_map(fn (array $row): string => $row['_variants']['author_name'], $rows));
}

describe('badge columns', function () {
    beforeEach(function () {
        Book::create(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
        Book::create(['title' => 'Beowulf', 'author_name' => 'Anonymous']);
        Book::create(['title' => 'Emma', 'author_name' => 'Jane Austen']);
    });

    it('describes itself as a badge column', function () {
        expect(tableResource(tableWithColumns([BadgeColumn::make('author_name', 'Author')]))['columns'])
            ->toBe([['name' => 'author_name', 'label' => 'Author', 'type' => 'badge']]);
    });

    it('shows the value as its text, like a text column', function () {
        expect(tableResource(tableWithColumns([TextColumn::make('title'), BadgeColumn::make('author_name')]))['rows'][0])
            ->toMatchArray(['title' => 'Dune', 'author_name' => 'Frank Herbert']);
    });

    it('rejects a map() result that is not text, like a text column', function () {
        $exception = invalidColumn(fn () => tableResource(tableWithColumns([BadgeColumn::make('author_name')->map(fn () => true)])));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.invalid-mapped-value')
            ->and($exception->getMessage())->toContain('[author_name]')
            ->and($exception->getMessage())->toContain('bool');
    });

    it('picks the variant mapped to the value, from a case or its name', function () {
        expect(authorVariants(BadgeColumn::make('author_name')->variant([
            'Frank Herbert' => Variant::Success,
            'Anonymous' => 'warning',
        ])))->toBe(['Dune' => 'success', 'Beowulf' => 'warning', 'Emma' => 'neutral']);
    });

    it('is neutral without a variant', function () {
        expect(authorVariants(BadgeColumn::make('author_name')))
            ->toBe(['Dune' => 'neutral', 'Beowulf' => 'neutral', 'Emma' => 'neutral']);
    });

    it('picks the variant the callback returns for the value and record, as a case, a name or null', function () {
        expect(authorVariants(BadgeColumn::make('author_name')->variant(fn (string $author, Book $book) => match ($book->title) {
            'Dune' => Variant::Info,
            'Beowulf' => $author === 'Anonymous' ? 'danger' : 'success',
            default => null,
        })))->toBe(['Dune' => 'info', 'Beowulf' => 'danger', 'Emma' => 'neutral']);
    });

    it('picks the variant by the value, not the text map() shows', function () {
        $badge = BadgeColumn::make('author_name')
            ->map(fn (string $author) => strtoupper($author))
            ->variant(['Anonymous' => Variant::Warning]);

        $rows = tableResource(tableWithColumns([TextColumn::make('title'), $badge]))['rows'];

        expect($rows[1])->toMatchArray(['author_name' => 'ANONYMOUS', '_variants' => ['author_name' => 'warning']]);
    });

    it('picks the variant mapped to the value of an enum cast attribute', function () {
        AuthorCastBooksTable::$badge = BadgeColumn::make('author_name')
            ->map(fn (BookAuthor $author) => $author->name)
            ->variant([BookAuthor::Anonymous->value => Variant::Warning]);

        expect(authorVariants(AuthorCastBooksTable::$badge, AuthorCastBooksTable::make()))
            ->toBe(['Dune' => 'neutral', 'Beowulf' => 'warning', 'Emma' => 'neutral']);
    });

    it('passes the cast value to the callback', function () {
        AuthorCastBooksTable::$badge = BadgeColumn::make('author_name')
            ->map(fn (BookAuthor $author) => $author->name)
            ->variant(fn (BookAuthor $author) => $author === BookAuthor::JaneAusten ? Variant::Success : null);

        expect(authorVariants(AuthorCastBooksTable::$badge, AuthorCastBooksTable::make()))
            ->toBe(['Dune' => 'neutral', 'Beowulf' => 'neutral', 'Emma' => 'success']);
    });

    it('sends no variants for columns that are not badges', function () {
        expect(tableResource(tableWithColumns([TextColumn::make('title'), BadgeColumn::make('author_name')]))['rows'][0]['_variants'])
            ->toBe(['author_name' => 'neutral']);
    });

    it('rejects an unknown variant name in the map before there are any rows', function () {
        $exception = invalidColumn(fn () => BadgeColumn::make('author_name')->variant(['Anonymous' => 'warn']));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.invalid-variant')
            ->and($exception->getMessage())->toContain('[author_name]')
            ->and($exception->getMessage())->toContain('"warn"');
    });

    it('rejects a map value that is not a variant', function () {
        $exception = invalidColumn(fn () => BadgeColumn::make('author_name')->variant(['Anonymous' => 3]));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.invalid-variant')
            ->and($exception->getMessage())->toContain('int');
    });

    it('rejects a callback result that is not a variant', function (mixed $result, string $shown) {
        $exception = invalidColumn(fn () => authorVariants(BadgeColumn::make('author_name')->variant(fn () => $result)));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.invalid-variant')
            ->and($exception->getMessage())->toContain('[author_name]')
            ->and($exception->getMessage())->toContain($shown);
    })->with([
        'unknown name' => ['Success', '"Success"'],
        'bool' => [true, 'bool'],
        'other enum' => [ImageStyle::Round, ImageStyle::class],
    ]);

    it('rejects a callback that changes the record', function () {
        $exception = invalidColumn(fn () => authorVariants(BadgeColumn::make('author_name')->variant(function (string $author, Book $book) {
            $book->title = 'Changed';

            return null;
        })));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.record-modified')
            ->and($exception->getMessage())->toContain('variant()');
    });

    it('rejects a map once the value can no longer match it, e.g. after a cast', function (string $cast, string $type) {
        $badge = fn () => BadgeColumn::make('author_name')->variant(['Frank Herbert' => Variant::Success]);

        // Valid: the string value matches the map.
        expect(authorVariants($badge()))->toBe(['Dune' => 'success', 'Beowulf' => 'neutral', 'Emma' => 'neutral']);

        // Invalid: the same column, with the attribute cast to a type no array key can equal.
        AuthorRecastBook::$cast = $cast;
        AuthorRecastBooksTable::$badge = $badge();
        $exception = invalidColumn(fn () => authorVariants(AuthorRecastBooksTable::$badge, AuthorRecastBooksTable::make()));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.unmatchable-variant-value')
            ->and($exception->getMessage())->toContain('[author_name]')
            ->and($exception->getMessage())->toContain($type);
    })->with([
        'bool' => ['boolean', 'bool'],
        'float' => ['float', 'float'],
    ]);

    it('lets a callback handle a value a map cannot match', function () {
        AuthorRecastBook::$cast = 'boolean';
        AuthorRecastBooksTable::$badge = BadgeColumn::make('author_name')
            ->map(fn (bool $value) => $value ? 'Yes' : 'No')
            ->variant(fn (bool $value) => $value ? Variant::Success : null);

        expect(authorVariants(AuthorRecastBooksTable::$badge, AuthorRecastBooksTable::make()))
            ->toBe(['Dune' => 'success', 'Beowulf' => 'success', 'Emma' => 'success']);
    });

    it('is neutral for a missing value, even with a map', function () {
        $badge = BadgeColumn::make('status')
            ->map(fn ($value) => $value ?? 'Unknown')
            ->variant(['Unknown' => Variant::Danger]);

        $rows = tableResource(tableWithColumns([TextColumn::make('title'), $badge]))['rows'];

        expect(array_column($rows, '_variants'))->toBe(array_fill(0, 3, ['status' => 'neutral']));
    });

    it('rejects calling variant() twice on a column', function () {
        $exception = invalidColumn(fn () => BadgeColumn::make('author_name')->variant([])->variant(fn () => null));

        expect($exception->errorCode())->toBe('larasell-admin.table.column.configured-twice')
            ->and($exception->getMessage())->toContain('variant()');
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
