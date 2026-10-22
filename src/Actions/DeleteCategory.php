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
        // its descendants and one DELETE removes them all. The derived table lets MySQL delete
        // from the table it reads. Products are only detached, by the pivot's cascade.
        $tree = DB::query()->fromRaw(
            '(with recursive tree (id) as (
                select id from larasell_categories where id = ?
                union all
                select child.id from larasell_categories as child join tree on child.parent_id = tree.id
            ) select id from tree) as tree',
            [$category->getKey()],
        )->select('id');

        Category::query()->whereIn('id', $tree)->delete();
    }
}
