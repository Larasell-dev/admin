<?php

declare(strict_types=1);

use Larasell\Admin\Models\Admin;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;

class AdminsTable extends Table
{
    protected ?string $model = Admin::class;

    public function columns(): array
    {
        return [
            TextColumn::make('name'),
            TextColumn::make('email', 'Email address'),
        ];
    }
}

class AdminAvatarsTable extends Table
{
    protected ?string $model = Admin::class;

    public function columns(): array
    {
        return [
            TextColumn::make('avatar_path'),
        ];
    }
}

function tableResource(Table $table): array
{
    return json_decode(json_encode($table), true);
}

it('describes its columns with a label derived from the column name', function () {
    expect(tableResource(AdminsTable::make())['columns'])->toBe([
        ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
        ['name' => 'email', 'label' => 'Email address', 'type' => 'text'],
    ]);
});

it('turns multi-word column names into a sentence case label', function () {
    expect(tableResource(AdminAvatarsTable::make())['columns'][0]['label'])->toBe('Avatar path');
});

it('returns a row per model with the value of each column', function () {
    $ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'secret-password']);
    $grace = Admin::create(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => 'secret-password']);

    expect(tableResource(AdminsTable::make())['rows'])->toBe([
        ['id' => $ada->id, 'name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
        ['id' => $grace->id, 'name' => 'Grace Hopper', 'email' => 'grace@example.com'],
    ]);
});

it('derives a sentence case title from the class name', function () {
    expect(tableResource(AdminAvatarsTable::make())['title'])->toBe('Admin avatars table');
});

it('returns no rows when there are no models', function () {
    expect(tableResource(AdminsTable::make())['rows'])->toBe([]);
});
