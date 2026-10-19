<?php

declare(strict_types=1);

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
        ['id' => $dune->id, 'title' => 'Dune', 'author_name' => 'Frank Herbert'],
        ['id' => $emma->id, 'title' => 'Emma', 'author_name' => 'Jane Austen'],
    ]);
});

it('derives a sentence case title from the class name', function () {
    expect(tableResource(BookAuthorsTable::make())['title'])->toBe('Book authors table');
});

it('returns no rows when there are no models', function () {
    expect(tableResource(BooksTable::make())['rows'])->toBe([]);
});
