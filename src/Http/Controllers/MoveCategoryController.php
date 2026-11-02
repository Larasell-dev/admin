<?php

namespace Larasell\Admin\Http\Controllers;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Models\Category;

class MoveCategoryController
{
    /**
     * Moves the category, with everything below it, under another category or to the root.
     * The position is where it goes among its new siblings, counted without itself; without
     * one, or past the last sibling, it goes last.
     */
    public function __invoke(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(Category::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($category): void {
                    if ($this->isWithin((int) $value, $category)) {
                        $fail('A category cannot be moved into itself or a category below it.');
                    }
                },
            ],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $parentId = $validated['parent_id'] ?? null;

        DB::transaction(function () use ($category, $parentId, $validated): void {
            $siblings = Category::query()
                ->where('parent_id', $parentId)
                ->whereKeyNot($category->getKey())
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $siblings->splice(min($validated['position'] ?? $siblings->count(), $siblings->count()), 0, [$category]);
            $category->parent_id = $parentId;

            // Numbers the siblings again from 0, saving only those whose position changed.
            foreach ($siblings->values() as $position => $sibling) {
                $sibling->position = $position;
                $sibling->save();
            }
        });

        return back();
    }

    /**
     * Whether the given category is the category itself or one below it, by walking up its parents.
     */
    protected function isWithin(int $id, Category $category): bool
    {
        return DB::query()->fromRaw(
            '(with recursive ancestors (id, parent_id) as (
                select id, parent_id from larasell_categories where id = ?
                union all
                select parent.id, parent.parent_id from larasell_categories as parent join ancestors on parent.id = ancestors.parent_id
            ) select id from ancestors) as ancestors',
            [$id],
        )->where('id', $category->getKey())->exists();
    }
}
