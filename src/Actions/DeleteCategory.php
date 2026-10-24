<?php

namespace Larasell\Admin\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Larasell\Larasell\Models\Category;

class DeleteCategory
{
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
                'confirmation' => "Type \"{$name}\" to confirm.",
            ]);
        }

        // Eloquent can't query a tree recursively, so a recursive CTE collects the category and
        // its descendants in one query. Each is deleted as a model so the Eloquent events fire,
        // deepest first: listeners still see the parent, which nullOnDelete would otherwise
        // detach. Products are only detached, by the pivot's cascade.
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

        DB::transaction(fn () => $categories->each->delete());
    }
}
