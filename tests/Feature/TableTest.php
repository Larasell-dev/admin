<?php

declare(strict_types=1);

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

function tableResource(Table $table): array
{
    return json_decode(json_encode($table), true);
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
