<?php

namespace Larasell\Admin\Category\Actions;

use Illuminate\Support\Facades\DB;
use Larasell\Larasell\Catalog\Actions\MoveCategory as CoreMoveCategory;
use Larasell\Larasell\Catalog\CategoryId;
use Larasell\Larasell\Catalog\Exceptions\CategoryCycle;
use Larasell\Larasell\Catalog\Models\Category;

class MoveCategory
{
    public function __construct(private CoreMoveCategory $move) {}

    /**
     * Moves the category, with everything below it, to its new parent and position, and numbers
     * its new siblings again from 0. A position past the last sibling puts it last.
     *
     * @throws CategoryCycle when the new parent is the category or one below it
     */
    public function handle(Category $category, MoveCategoryPayload $payload): void
    {
        DB::transaction(function () use ($category, $payload): void {
            // The core action changes the parent: it refuses cycles and announces the move.
            $category = $this->move->handle(
                CategoryId::of($category),
                $payload->parentId === null ? null : new CategoryId($payload->parentId),
            );

            $siblings = Category::query()
                ->where('parent_id', $payload->parentId)
                ->whereKeyNot($category->getKey())
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $siblings->splice(min($payload->position ?? $siblings->count(), $siblings->count()), 0, [$category]);
            // Saving only writes the categories whose parent or position changed.
            foreach ($siblings->values() as $position => $sibling) {
                $sibling->position = $position;
                $sibling->save();
            }
        });
    }
}
