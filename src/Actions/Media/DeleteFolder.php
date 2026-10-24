<?php

namespace Larasell\Admin\Actions\Media;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Models\Media\Folder;

class DeleteFolder
{
    /**
     * Deletes the folder and every folder below it.
     */
    public function handle(Folder $folder): void
    {
        // Eloquent can't query a tree recursively, so a recursive CTE collects the folder and
        // its descendants in one query. Each is deleted as a model so the Eloquent events fire,
        // deepest first: listeners still see the parent, and no folder is deleted while the
        // parent_id of another still points at it.
        $tree = DB::query()->fromRaw(
            '(with recursive tree (id, depth) as (
                select id, 0 from larasell_admin_media_folders where id = ?
                union all
                select child.id, tree.depth + 1 from larasell_admin_media_folders as child join tree on child.parent_id = tree.id
            ) select id, depth from tree) as tree',
            [$folder->getKey()],
        );

        $folders = Folder::query()
            ->joinSub($tree, 'tree', 'tree.id', '=', 'larasell_admin_media_folders.id')
            ->orderByDesc('tree.depth')
            ->select('larasell_admin_media_folders.*')
            ->get();

        DB::transaction(fn () => $folders->each->delete());
    }
}
