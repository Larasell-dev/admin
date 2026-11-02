<?php

declare(strict_types=1);

use Larasell\Admin\Actions\MoveCategory;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Payloads\MoveCategoryPayload;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Translatable;

function categoryToMove(string $name, ?Category $parent = null): Category
{
    return Category::create([
        'parent_id' => $parent?->id,
        'slug' => Translatable::fromString(str($name)->slug()->value()),
        'name' => Translatable::fromString($name),
    ]);
}

/** @return list<array{string, int}> */
function namesAndPositions(?Category $parent): array
{
    return Category::query()
        ->where('parent_id', $parent?->id)
        ->orderBy('position')
        ->get()
        ->map(fn (Category $category): array => [$category->name->get(), $category->position])
        ->all();
}

it('moves a category into another at a position and numbers the new siblings from 0', function () {
    $clothing = categoryToMove('Clothing');
    categoryToMove('Shirts', $clothing);
    categoryToMove('Trousers', $clothing);
    $shoes = categoryToMove('Shoes');

    app(MoveCategory::class)->handle($shoes, new MoveCategoryPayload(parentId: $clothing->id, position: 1));

    expect(namesAndPositions($clothing))->toBe([['Shirts', 0], ['Shoes', 1], ['Trousers', 2]])
        ->and($shoes->fresh()->parent_id)->toBe($clothing->id);
});

it('moves a category last without a position or past its last sibling', function (?int $position) {
    $clothing = categoryToMove('Clothing');
    $shoes = categoryToMove('Shoes');
    categoryToMove('Sale');

    app(MoveCategory::class)->handle($clothing, new MoveCategoryPayload(parentId: null, position: $position));

    expect(namesAndPositions(null))->toBe([['Shoes', 0], ['Sale', 1], ['Clothing', 2]])
        ->and($shoes->fresh()->position)->toBe(0);
})->with([
    'without a position' => [null],
    'past the end' => [99],
]);

it('reads the parent and position from form input sent as strings', function () {
    $clothing = categoryToMove('Clothing');
    categoryToMove('Shirts', $clothing);
    categoryToMove('Trousers', $clothing);
    $shoes = categoryToMove('Shoes');

    $this->actingAs(Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']), 'larasell-admin')
        ->post(route('larasell.admin.categories.move', $shoes), ['parent_id' => (string) $clothing->id, 'position' => '0'])
        ->assertSessionHasNoErrors();

    expect(namesAndPositions($clothing))->toBe([['Shoes', 0], ['Shirts', 1], ['Trousers', 2]]);
});
