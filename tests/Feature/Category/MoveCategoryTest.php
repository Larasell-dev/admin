<?php

declare(strict_types=1);

use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Translatable;

function categoryToMove(string $name, ?Category $parent = null): Category
{
    return Category::create([
        'parent_id' => $parent?->id,
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

it('reads the parent and position from form input sent as strings', function () {
    // Arrange
    $clothing = categoryToMove('Clothing');
    categoryToMove('Shirts', $clothing);
    categoryToMove('Trousers', $clothing);
    $shoes = categoryToMove('Shoes');

    // Act
    $this->actingAs(Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']), 'larasell-admin')
        ->post(route('larasell.admin.categories.move', $shoes), ['parent_id' => (string) $clothing->id, 'position' => '0'])
        ->assertSessionHasNoErrors();

    // Assert
    expect(namesAndPositions($clothing))->toBe([['Shoes', 0], ['Shirts', 1], ['Trousers', 2]]);
});
