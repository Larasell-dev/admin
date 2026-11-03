<?php

namespace Larasell\Admin\Actions\Categories;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Payloads\Categories\MoveCategoryPayload;
use Larasell\Larasell\Models\Category;

class MoveCategory
{
    /**
     * Moves the category, with everything below it, to its new parent and position, and numbers
     * its new siblings again from 0. A position past the last sibling puts it last.
     */
    public function handle(Category $category, MoveCategoryPayload $payload): void
    {
        DB::transaction(function () use ($category, $payload): void {
            $siblings = Category::query()
                ->where('parent_id', $payload->parentId)
                ->whereKeyNot($category->getKey())
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $siblings->splice(min($payload->position ?? $siblings->count(), $siblings->count()), 0, [$category]);
            $category->parent_id = $payload->parentId;

            // Saving only writes the categories whose parent or position changed.
            foreach ($siblings->values() as $position => $sibling) {
                $sibling->position = $position;
                $sibling->save();
            }
        });
    }
}
