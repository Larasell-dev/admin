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
        ]);

        $category->update(['parent_id' => $validated['parent_id'] ?? null]);

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
