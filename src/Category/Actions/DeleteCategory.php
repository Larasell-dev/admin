<?php

namespace Larasell\Admin\Category\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Larasell\Larasell\Catalog\Actions\DeleteCategory as CoreDeleteCategory;
use Larasell\Larasell\Catalog\CategoryId;
use Larasell\Larasell\Catalog\Models\Category;

class DeleteCategory
{
    public function __construct(private CoreDeleteCategory $delete) {}

    /**
     * Deletes the category and all categories below it, once the admin typed its name.
     *
     * @throws ValidationException
     */
    public function handle(Category $category, ?string $confirmation): void
    {
        $name = $category->name->get();

        if ($confirmation !== $name) {
            throw ValidationException::withMessages([
                'confirmation' => __('Type ":text" to confirm.', ['text' => $name]),
            ]);
        }

        // Eloquent can't query a tree recursively, so a recursive CTE collects the category and
        // its descendants in one query. The core action deletes one category and makes its children
        // top-level, so each is deleted on its own, deepest first: none is left without its parent.
        // Products are only detached, by the pivot's cascade.
        $tree = DB::query()->fromRaw(
            '(with recursive tree (id, depth) as (
                select id, 0 from larasell_categories where id = ?
                union all
                select child.id, tree.depth + 1 from larasell_categories as child join tree on child.parent_id = tree.id
            ) select id, depth from tree) as tree',
            [$category->getKey()],
        );

        $categories = Category::query()
            ->joinSub($tree, 'tree', 'tree.id', '=', 'larasell_categories.id')
            ->orderByDesc('tree.depth')
            ->select('larasell_categories.*')
            ->get();

        DB::transaction(fn () => $categories->each(fn (Category $category) => $this->delete->handle(CategoryId::of($category))));
    }
}
