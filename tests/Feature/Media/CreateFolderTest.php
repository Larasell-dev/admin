<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Actions\Media\CreateFolder;
use Larasell\Admin\Models\Media\Folder;

it('creates a top-level folder without a parent', function () {
    $folder = app(CreateFolder::class)->handle('Banners', null);

    expect($folder->fresh())
        ->name->toBe('Banners')
        ->parent_id->toBeNull();
});

it('creates a folder inside the given parent folder', function () {
    Folder::create(['name' => 'Other']);
    $parent = Folder::create(['name' => 'Campaigns']);

    $folder = app(CreateFolder::class)->handle('Summer', $parent);

    expect($folder->fresh()->parent->is($parent))->toBeTrue();
});

it('creates a folder inside the parent with the given id', function () {
    Folder::create(['name' => 'Other']);
    $parent = Folder::create(['name' => 'Campaigns']);

    $folder = app(CreateFolder::class)->handle('Summer', $parent->id);

    expect($folder->fresh()->parent_id)->toBe($parent->id);
});

it('throws when no folder has the parent id', function () {
    app(CreateFolder::class)->handle('Summer', 999);
})->throws(ModelNotFoundException::class);

it('requires a name', function (string $name) {
    try {
        app(CreateFolder::class)->handle($name, null);
    } finally {
        expect(Folder::count())->toBe(0);
    }
})->with(['empty' => '', 'blank' => '   ', 'too long' => str_repeat('a', 256)])->throws(ValidationException::class);
